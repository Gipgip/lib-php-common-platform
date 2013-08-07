<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Platform\Utility;

use DreamFactory\Common\Utility\DataFormat;
use DreamFactory\Platform\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * RestResponse
 * REST Response Utilities
 */
class RestResponse extends HttpResponse
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const GZIP_THRESHOLD = 2048;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param int $code
	 *
	 * @return string
	 */
	public static function getHttpStatusCodeTitle( $code )
	{
		return implode( ' ', preg_split( '/(?=[A-Z])/', static::nameOf( $code ), -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * @param int $code
	 *
	 * @return int
	 */
	public static function getHttpStatusCode( $code )
	{
		//	If not valid code, return 500 - server error
		return !static::contains( $code ) ? static::InternalServerError : $code;
	}

	/**
	 * @param \Exception $ex
	 * @param string     $desired_format
	 */
	public static function sendErrors( $ex, $desired_format = 'json' )
	{
		$_status = static::InternalServerError;

		if ( $ex instanceof RestException || $ex instanceOf \CHttpException )
		{
			$_status = $ex->statusCode;
		}
		else if ( $ex instanceof \DreamFactory\Platform\Exceptions\RestException )
		{
			$_status = $ex->getStatusCode();
		}

		$result = array(
			"error" => array(
				array(
					"message" => htmlentities( $ex->getMessage() ),
					"code"    => $ex->getCode()
				)
			)
		);

		if ( static::Ok != $_status )
		{
			if ( $_status == static::InternalServerError || $_status == static::BadRequest )
			{
				Log::error( 'Error ' . $_status . ': ' . $ex->getMessage() );
			}
			else
			{
				Log::info( 'Non-Error ' . $_status . ': ' . $ex->getMessage() );
			}
		}

		static::sendResults( $result, $_status, null, $desired_format );
	}

	/**
	 * @param        $result
	 * @param int    $code
	 * @param null   $result_format
	 * @param string $desired_format
	 */
	public static function sendResults( $result, $code = RestResponse::Ok, $result_format = null, $desired_format = 'json' )
	{
		//	Some REST services may handle the response, they just return null
		if ( !is_null( $result ) )
		{
			$code = static::getHttpStatusCode( $code );
			$title = static::getHttpStatusCodeTitle( $code );
			header( "HTTP/1.1 $code $title" );
			$result = DataFormat::reformatData( $result, $result_format, $desired_format );
			switch ( $desired_format )
			{
				case 'json':
					static::sendJsonResponse( $result );
					break;

				case 'xml':
					static::sendXmlResponse( $result );
					break;
			}

			//	Add additional headers for CORS support
			Pii::app()->addCorsHeaders();
		}

		Pii::end();
	}

	/**
	 * @todo this function needs to be revisited
	 */
	public static function sendResponse()
	{
		$_encoding = ( !headers_sent() && false !== strpos( Option::server( 'HTTP_ACCEPT_ENCODING' ), 'gzip' ) );

		//	IE 9 requires hoop for session cookies in iframes
		if ( !headers_sent() )
		{
			header( 'P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"' );
		}

		if ( $_encoding )
		{
			$_output = ob_get_clean();

			if ( strlen( $_output ) < static::GZIP_THRESHOLD )
			{
				//	no need to waste resources in compressing very little data
				echo $_output;
			}
			else
			{
				header( 'Content-Encoding: gzip' );
				echo gzencode( $_output, 9 );
			}

			return;
		}

		ob_end_flush();
	}

	/**
	 * @param $data
	 */
	public static function sendXmlResponse( $data )
	{
		/* gzip handling output if necessary */
		ob_start();
		ob_implicit_flush( 0 );

		header( 'Content-type: application/xml' );
		echo "<?xml version=\"1.0\" ?>\n<dfapi>\n" . $data . "</dfapi>";
		self::sendResponse();
	}

	/**
	 * @param string $data Data already in json format - see uses
	 */
	public static function sendJsonResponse( $data )
	{
		/* gzip handling output if necessary */
		ob_start();
		ob_implicit_flush( 0 );

		header( 'Content-type: application/json; charset=utf-8' );

		// JSON if no callback
		if ( isset( $_GET['callback'] ) )
		{
			// JSONP if valid callback
			if ( static::is_valid_callback( $_GET['callback'] ) )
			{
				$data = "{$_GET['callback']}($data);";
			}
			else
			{
				// Otherwise, bad request
				header( 'status: 400 Bad Request', true, static::BadRequest );
			}
		}
		echo $data;
	}

	/**
	 * @param $subject
	 *
	 * @return bool
	 */
	public static function is_valid_callback( $subject )
	{
		$identifier_syntax
			= '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

		$reserved_words = array(
			'break',
			'do',
			'instanceof',
			'typeof',
			'case',
			'else',
			'new',
			'var',
			'catch',
			'finally',
			'return',
			'void',
			'continue',
			'for',
			'switch',
			'while',
			'debugger',
			'function',
			'this',
			'with',
			'default',
			'if',
			'throw',
			'delete',
			'in',
			'try',
			'class',
			'enum',
			'extends',
			'super',
			'const',
			'export',
			'import',
			'implements',
			'let',
			'private',
			'public',
			'yield',
			'interface',
			'package',
			'protected',
			'static',
			'null',
			'true',
			'false'
		);

		return preg_match( $identifier_syntax, $subject )
			   && !in_array( mb_strtolower( $subject, 'UTF-8' ), $reserved_words );
	}
}
