<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Platform\Utility;

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use DreamFactory\Platform\Enums\PlatformStorageTypes;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Exceptions\InternalServerErrorException;
use DreamFactory\Platform\Exceptions\NotFoundException;
use DreamFactory\Platform\Exceptions\RestException;
use DreamFactory\Platform\Services\BasePlatformRestService;
use DreamFactory\Platform\Services\BasePlatformService;
use DreamFactory\Platform\Yii\Models\Service;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * ServiceHandler
 * DSP service factory
 */
class ServiceHandler
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array Created services
	 */
	protected static $_serviceCache = array();
	/**
	 * @var array The services available
	 */
	protected static $_serviceConfig = array();
	/**
	 * @var array
	 */
	protected static $_baseServices
		= array(
			'system'   => 'DreamFactory\\Platform\\Services\\SystemManager',
			'user'     => 'DreamFactory\\Platform\\Services\\UserManager',
			'api_docs' => 'DreamFactory\\Platform\\Services\\SwaggerManager',
		);

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param int|string $serviceId
	 * @param bool       $checkActive
	 *
	 * @return \DreamFactory\Platform\Services\BasePlatformRestService
	 */
	public static function getService( $serviceId, $checkActive = false )
	{
		return static::getServiceObject( $serviceId, $checkActive );
	}

	/**
	 * Retrieves the pointer to the particular service handler
	 *
	 * If the service is already created, it just returns the private class
	 * member that holds the pointer, otherwise it calls the constructor for
	 * the new service, passing in parameters based on the stored configuration settings.
	 *
	 * @param int|string $api_name
	 * @param boolean    $check_active Throws an exception if true and the service is not active.
	 *
	 * @return BasePlatformRestService The new or previously constructed XXXSvc
	 * @throws \Exception if construction of service is not possible
	 */
	public static function getServiceObject( $api_name, $check_active = false )
	{
		if ( empty( static::$_serviceConfig ) )
		{
			static::$_serviceConfig = Pii::getParam( 'dsp.service_config', array() );
		}

		if ( empty( static::$_serviceCache ) )
		{
			static::$_serviceCache = Pii::getState( 'dsp.service_cache' );
		}

		$_tag = strtolower( trim( $api_name ) );

		//	Cached?
		if ( null !== ( $_service = Option::get( static::$_serviceCache, $_tag ) ) )
		{
			return $_service;
		}

		//	A base service?
		if ( isset( static::$_baseServices[$_tag] ) )
		{
			return new static::$_baseServices[$_tag];
		}

		try
		{
			if ( null === ( $_config = Service::model()->byServiceId( $_tag )->find() ) )
			{
				throw new NotFoundException( 'Service not found' );
			}

			$_service = static::_createService( $_config->getAttributes( null ) );

			if ( $check_active && !$_service->getIsActive() )
			{
				throw new BadRequestException( 'Requested service "' . $_tag . '" is not active.' );
			}

			if ( !property_exists( $_service, '_dbConn' ) )
			{
				static::$_serviceCache[$_tag] = $_service;
				Pii::setState( 'dsp.service_cache', static::$_serviceCache );
			}

			return $_service;
		}
		catch ( \Exception $_ex )
		{
			throw new InternalServerErrorException( 'Failed to launch service "' . $_tag . '": ' . $_ex->getMessage() );
		}
	}

	/**
	 * Retrieves the pointer to the particular service handler
	 *
	 * If the service is already created, it just returns the private class
	 * member that holds the pointer, otherwise it calls the constructor for
	 * the new service, passing in parameters based on the stored configuration settings.
	 *
	 * @param int     $id
	 * @param boolean $check_active Throws an exception if true and the service is not active.
	 *
	 * @return BasePlatformRestService The new or previously constructed XXXSvc
	 * @throws \Exception if construction of service is not possible
	 */
	public static function getServiceObjectById( $id, $check_active = false )
	{
		return static::getServiceObject( $id, $check_active );
	}

	/**
	 * Creates a new instance of a configured service
	 *
	 * @param array $record
	 *
	 * @return BasePlatformRestService
	 * @throws \InvalidArgumentException
	 */
	protected static function _createService( $record )
	{
		$_serviceTypeId = trim( strtolower( Option::get( $record, 'type_id', PlatformServiceTypes::SYSTEM_SERVICE ) ) );

		if ( null === ( $_config = Option::get( static::$_serviceConfig, $_serviceTypeId ) ) )
		{
			throw new \InvalidArgumentException( 'Service type "' . Option::get( $record, 'type' ) . '" is invalid.' );
		}

		if ( null !== ( $_serviceClass = Option::get( $_config, 'class' ) ) )
		{
			if ( is_array( $_serviceClass ) )
			{
				$_config = Option::get( $_serviceClass, Option::get( $record, 'storage_type_id' ) );
				$_serviceClass = Option::get( $_config, 'class' );
			}

			unset( $record['native_format'] );

			$_arguments = array( $record, Option::get( $_config, 'local', true ) );

			$_mirror = new \ReflectionClass( $_serviceClass );

			return $_mirror->newInstanceArgs( $_arguments );
		}

		throw new \InvalidArgumentException( 'The service requested is invalid.' );
	}
}
