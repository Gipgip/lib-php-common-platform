<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
namespace DreamFactory\Platform\Services;

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Exceptions\ForbiddenException;
use DreamFactory\Platform\Exceptions\InternalServerErrorException;
use DreamFactory\Platform\Exceptions\NotFoundException;
use DreamFactory\Platform\Exceptions\UnauthorizedException;
use DreamFactory\Platform\Resources\User\Session;
use DreamFactory\Platform\Utility\RestData;
use DreamFactory\Platform\Utility\Utilities;
use DreamFactory\Platform\Yii\Models\Config;
use DreamFactory\Platform\Yii\Models\User;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Sql;
use Swagger\Annotations as SWG;

/**
 * UserManager
 * DSP user manager
 *
 * @package
 * @category
 *
 * @SWG\Resource(
 *   resourcePath="/user"
 * )
 *
 * @SWG\Model(id="Profile",
 * @SWG\Property(name="email",type="string",description="Email address of the current user."),
 * @SWG\Property(name="first_name",type="string",description="First name of the current user."),
 * @SWG\Property(name="last_name",type="string",description="Last name of the current user."),
 * @SWG\Property(name="display_name",type="string",description="Full display name of the current user."),
 * @SWG\Property(name="phone",type="string",description="Phone number."),
 * @SWG\Property(name="security_question",type="string",description="Question to be answered to initiate password reset."),
 * @SWG\Property(name="default_app_id",type="int",description="Id of the application to be launched at login.")
 * )
 * @SWG\Model(id="Register",
 * @SWG\Property(name="email",type="string",description="Email address of the current user."),
 * @SWG\Property(name="first_name",type="string",description="First name of the current user."),
 * @SWG\Property(name="last_name",type="string",description="Last name of the current user."),
 * @SWG\Property(name="display_name",type="string",description="Full display name of the current user.")
 * )
 * @SWG\Model(id="Confirm",
 * @SWG\Property(name="email",type="string"),
 * @SWG\Property(name="new_password",type="string")
 * )
 * @SWG\Model(id="Password",
 * @SWG\Property(name="old_password",type="string"),
 * @SWG\Property(name="new_password",type="string")
 * )
 * @SWG\Model(id="Question",
 * @SWG\Property(name="security_question",type="string")
 * )
 * @SWG\Model(id="Answer",
 * @SWG\Property(name="email",type="string"),
 * @SWG\Property(name="security_answer",type="string")
 * )
 *
 */
class UserManager extends BaseSystemRestService
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected static $_randKey;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new UserManager
	 *
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				 'name'        => 'User Session Management',
				 'apiName'     => 'user',
				 'type'        => 'User',
				 'type_id'     => PlatformServiceTypes::SYSTEM_SERVICE,
				 'description' => 'Service for a user to manage their session, profile and password.',
				 'is_active'   => true,
			)
		);

		//	For better security. Get a random string from this link: http://tinyurl.com/randstr and put it here
		static::$_randKey = \sha1( Pii::db()->password );
	}

	/**
	 * @SWG\Api(
	 *       path="/user", description="Operations available for user session management.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List resources available for user session management.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources"
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 */
	protected function _listResources()
	{
		$resources = array(
			array( 'name' => 'session' ),
			array( 'name' => 'profile' ),
			array( 'name' => 'password' ),
			array( 'name' => 'challenge' ),
			array( 'name' => 'register' ),
			array( 'name' => 'confirm' ),
			array( 'name' => 'ticket' )
		);

		return array( 'resource' => $resources );
	}

	/**
	 *
	 * @return array|bool
	 * @throws BadRequestException
	 */
	protected function _handleResource()
	{
		$_authClient = null;

		switch ( $this->_resource )
		{
			case '':
				switch ( $this->_action )
				{
					case self::Get:
						return $this->_listResources();
						break;
					default:
						return false;
				}
				break;

			case 'session':
				//	Handle remote login
				if ( HttpMethod::Post == $this->_action && Pii::getParam( 'dsp.allow_remote_logins' ) )
				{
					$_provider = FilterInput::post( 'provider', 'facebook', FILTER_SANITIZE_STRING );

					if ( !empty( $_provider ) )
					{
						if ( null !== ( $_config = Pii::getParam( 'dsp.remote_login_options' ) ) )
						{
							$_config['base_url'] = Pii::baseUrl( true ) . Pii::url( $_config['base_url'] );
							$_authClient = new \Hybrid_Auth( $_config );
							$_adapter = $_authClient->authenticate( $_provider );

							if ( is_string( $_adapter ) )
							{
								//	This is an url...
							}

							$_profile = $_adapter->getUserProfile();

							Log::debug( 'Profile from "' . $_provider . '": ' . print_r( $_profile, true ) );
						}
					}
				}

				if ( null === $_authClient )
				{
					$obj = new Session( $this );
					$result = $obj->processRequest( null, $this->_action );
				}
				break;

			case 'profile':
				switch ( $this->_action )
				{
					case self::Get:
						$result = $this->getProfile();
						break;
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = RestData::getPostDataAsArray();
						$result = $this->changeProfile( $data );
						break;
					default:
						return false;
				}
				break;

			case 'password':
				switch ( $this->_action )
				{
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = RestData::getPostDataAsArray();
						$oldPassword = Utilities::getArrayValue( 'old_password', $data, '' );
						//$oldPassword = Utilities::decryptPassword($oldPassword);
						$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
						//$newPassword = Utilities::decryptPassword($newPassword);
						$result = $this->changePassword( $oldPassword, $newPassword );
						break;
					default:
						return false;
				}
				break;

			case 'register':
				switch ( $this->_action )
				{
					case self::Post:
						$data = RestData::getPostDataAsArray();
						$firstName = Utilities::getArrayValue( 'first_name', $data, '' );
						$lastName = Utilities::getArrayValue( 'last_name', $data, '' );
						$displayName = Utilities::getArrayValue( 'display_name', $data, '' );
						$email = Utilities::getArrayValue( 'email', $data, '' );
						$result = $this->userRegister( $email, $firstName, $lastName, $displayName );
						break;
					default:
						return false;
				}
				break;

			case 'confirm':
				switch ( $this->_action )
				{
					case self::Post:
						$data = RestData::getPostDataAsArray();
						$code = Utilities::getArrayValue( 'code', $_REQUEST, '' );
						if ( empty( $code ) )
						{
							$code = Utilities::getArrayValue( 'code', $data, '' );
						}
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						if ( empty( $email ) )
						{
							$email = Utilities::getArrayValue( 'email', $data, '' );
						}
						if ( empty( $email ) && !empty( $code ) )
						{
							throw new BadRequestException( "Missing required email or code for invitation." );
						}
						$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
						if ( empty( $newPassword ) )
						{
							throw new BadRequestException( "Missing required fields 'new_password'." );
						}
						if ( !empty( $code ) )
						{
							$result = $this->passwordResetByCode( $code, $newPassword );
						}
						else
						{
							$result = $this->passwordResetByEmail( $email, $newPassword );
						}
						break;
					default:
						return false;
				}
				break;

			case 'challenge':
				switch ( $this->_action )
				{
					case self::Get:
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						$result = $this->getChallenge( $email );
						break;
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = RestData::getPostDataAsArray();
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						if ( empty( $email ) )
						{
							$email = Utilities::getArrayValue( 'email', $data, '' );
						}
						$answer = Utilities::getArrayValue( 'security_answer', $data, '' );
						if ( !empty( $email ) && !empty( $answer ) )
						{
							$result = $this->userSecurityAnswer( $email, $answer );
						}
						else
						{
							throw new BadRequestException( "Missing required fields 'email' and 'security_answer'." );
						}
						break;
					default:
						return false;
				}
				break;
			case 'ticket':
				switch ( $this->_action )
				{
					case self::Get:
						$result = $this->userTicket();
						break;
					default:
						return false;
				}
				break;
			default:
				return false;
				break;
		}

		return $result;
	}

	//-------- User Operations ------------------------------------------------

	/**
	 * @param $email
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function userInvite( $email )
	{
		if ( empty( $email ) )
		{
			throw new BadRequestException( "The email field for invitation can not be empty." );
		}
		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
		if ( empty( $theUser ) )
		{
			throw new BadRequestException( "No user currently exists with the email '$email'." );
		}
		$confirmCode = $theUser->confirm_code;
		if ( 'y' == $confirmCode )
		{
			throw new BadRequestException( "User with email '$email' has already confirmed registration in the system." );
		}
		try
		{
			if ( empty( $confirmCode ) )
			{
				$confirmCode = static::_makeConfirmationMd5( $email );
				$record = array( 'confirm_code' => $confirmCode );
				$theUser->setAttributes( $record );
				$theUser->save();
			}

			// generate link
			$link = Pii::app()->createAbsoluteUrl( 'public/launchpad/confirm.html' );
			$link .= '?email=' . urlencode( $email ) . '&code=' . urlencode( $confirmCode );

			return $link;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to generate user invite!\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *           path="/user/register", description="Operations on a user's security challenge.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="POST", summary="Register a new user in the system.",
	 *           notes="The new user is created and sent an email for confirmation.",
	 *           responseClass="Success", nickname="registerUser",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="registration", description="Data containing name-value pairs for new user registration.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Register"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param string $email
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $display_name
	 *
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return array
	 */
	public static function userRegister( $email, $first_name = '', $last_name = '', $display_name = '' )
	{
		if ( empty( $email ) )
		{
			throw new BadRequestException( "The email field for User can not be empty." );
		}

		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );

		if ( null !== $theUser )
		{
			throw new BadRequestException( "A User already exists with the email '$email'." );
		}

		/** @var $config Config */
		if ( null === ( $config = Config::model()->find( array( 'select' => 'allow_open_registration, open_reg_role_id' ) ) ) )
		{
			throw new \DreamFactory\Platform\Exceptions\InternalServerErrorException( 'Unable to load configuration.' );
		}

		if ( $config->allow_open_registration )
		{
			throw new BadRequestException( "Open registration for user accounts is not currently active for this system." );
		}

		$roleId = $config->open_reg_role_id;
		$confirmCode = static::_makeConfirmationMd5( $email );
		// fill out the user fields for creation
		$temp = substr( $email, 0, strrpos( $email, '@' ) );
		$fields = array(
			'email'        => $email,
			'first_name'   => ( !empty( $first_name ) ) ? $first_name : $temp,
			'last_name'    => ( !empty( $last_name ) ) ? $last_name : $temp,
			'display_name' => ( !empty( $display_name ) )
				? $display_name
				: ( !empty( $first_name ) && !empty( $last_name ) ) ? $first_name . ' ' . $last_name : $temp,
			'role_id'      => $roleId,
			'confirm_code' => $confirmCode
		);
		try
		{
			$user = new \User();
			$user->setAttributes( $fields );
			$user->save();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to register new user!\n{$ex->getMessage()}", $ex->getCode() );
		}

		return array( 'success' => true );
	}

	/**
	 * @SWG\Api(
	 *           path="/user/confirm", description="Operations on a user's confirmation.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="POST", summary="Confirm a new user registration or password change request.",
	 *           notes="The new user is confirmed and assumes the role given by system admin.",
	 *           responseClass="Success", nickname="confirmUser",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="confirmation", description="Data containing name-value pairs for new user confirmation.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Confirm"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $code
	 *
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return mixed
	 */
	public static function userConfirm( $code )
	{
		try
		{
			$theUser = User::model()->find( 'confirm_code=:cc', array( ':cc' => $code ) );
			if ( null === $theUser )
			{
				throw new BadRequestException( "Invalid confirm code." );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->save();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error validating confirmation.\n{$ex->getMessage()}", $ex->getCode() );
		}

		return array( 'success' => true );
	}

	/**
	 * @SWG\Api(
	 *           path="/user/challenge", description="Operations on a user's security challenge.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="GET", summary="Retrieve the security challenge question for the given user.",
	 *           notes="Use this question to challenge the user..",
	 *           responseClass="Question", nickname="getChallenge",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="email",
	 *           description="User email used to request security question.",
	 *           paramType="query",
	 *           dataType="string",
	 *           defaultValue="user@mycompany.com",
	 *           required="true",
	 *           allowMultiple=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $email
	 *
	 * @throws NotFoundException
	 * @throws ForbiddenException
	 * @return string
	 */
	public static function getChallenge( $email )
	{
		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
		if ( null === $theUser )
		{
			// bad email
			throw new NotFoundException( "The supplied email was not found in the system." );
		}
		if ( 'y' !== $theUser->getAttribute( 'confirm_code' ) )
		{
			throw new ForbiddenException( "Login registration has not been confirmed." );
		}
		$question = $theUser->getAttribute( 'security_question' );
		if ( !empty( $question ) )
		{
			return array( 'security_question' => $question );
		}
		else
		{
			throw new NotFoundException( 'No valid security question provisioned for this user.' );
		}
	}

	/**
	 * userTicket generates a SSO timed ticket for current valid session
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function userTicket()
	{
		try
		{
			$userId = Session::validateSession();
		}
		catch ( \Exception $ex )
		{
			Session::userLogout();
			throw $ex;
		}
		// regenerate new timed ticket
		$timestamp = time();
		$ticket = Utilities::encryptCreds( "$userId,$timestamp", "gorilla" );

		return array( 'ticket' => $ticket, 'ticket_expiry' => time() + ( 5 * 60 ) );
	}

	/**
	 * @param      $email
	 * @param bool $send_email
	 *
	 * @throws \Exception
	 * @return string
	 */
	public static function forgotPassword( $email, $send_email = false )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad email
				throw new \Exception( "The supplied email was not found in the system." );
			}
			if ( 'y' !== $theUser->confirm_code )
			{
				throw new \Exception( "Login registration has not been confirmed." );
			}
			if ( $send_email )
			{
				$email = $theUser->email;
				$fullName = $theUser->display_name;
				if ( !empty( $email ) && !empty( $fullName ) )
				{
//					static::sendResetPasswordLink( $email, $fullName );

					return array( 'success' => true );
				}
				else
				{
					throw new \Exception( 'No valid email provisioned for this user.' );
				}
			}
			else
			{
				$question = $theUser->security_question;
				if ( !empty( $question ) )
				{
					return array( 'security_question' => $question );
				}
				else
				{
					throw new \Exception( 'No valid security question provisioned for this user.' );
				}
			}
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error with password challenge.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *           path="/user/challenge", description="Operations on a user's security challenge.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="POST", summary="Answer the security challenge question for the given user.",
	 *           notes="Use this to gain temporary access to change password.",
	 *           responseClass="Session", nickname="answerChallenge",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="answer", description="Answer to the security question.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Answer"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $email
	 * @param $answer
	 *
	 * @throws UnauthorizedException
	 * @throws \Exception
	 * @return mixed
	 */
	public static function userSecurityAnswer( $email, $answer )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad email
				throw new \Exception( "The supplied email was not found in the system." );
			}
			if ( 'y' !== $theUser->confirm_code )
			{
				throw new \Exception( "Login registration has not been confirmed." );
			}
			// validate answer
			if ( !\CPasswordHelper::verifyPassword( $answer, $theUser->security_answer ) )
			{
				throw new UnauthorizedException( "The challenge response supplied does not match system records." );
			}

			Pii::user()->setId( $theUser->id );
			$isSysAdmin = $theUser->is_sys_admin;
			$result = Session::generateSessionDataFromUser( null, $theUser );

			// write back login datetime
			$theUser->last_login_date = date( 'c' );
			$theUser->save();

			Session::setCurrentUserId( $theUser->id );

			// additional stuff for session - launchpad mainly
			return Session::addSessionExtras( $result, $isSysAdmin, true );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error processing security answer.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param string $code
	 * @param string $new_password
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public static function passwordResetByCode( $code, $new_password )
	{
		try
		{
			$theUser = User::model()->find( 'confirm_code=:cc', array( ':cc' => $code ) );
			if ( null === $theUser )
			{
				// bad code
				throw new \Exception( "The supplied confirmation was not found in the system." );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->setAttribute( 'password', \CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error processing password reset.\n{$ex->getMessage()}", $ex->getCode() );
		}

		try
		{
			return Session::userLogin( $theUser->email, $new_password );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Password set, but failed to create a session.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param string $email
	 * @param string $new_password
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public static function passwordResetByEmail( $email, $new_password )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad code
				throw new \Exception( "The supplied email was not found in the system." );
			}
			$confirmCode = $theUser->confirm_code;
			if ( empty( $confirmCode ) || ( 'y' == $confirmCode ) )
			{
				throw new \Exception( "No invitation was found for the supplied email." );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->setAttribute( 'password', \CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error processing password reset.\n{$ex->getMessage()}", $ex->getCode() );
		}

		try
		{
			return Session::userLogin( $email, $new_password );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Password set, but failed to create a session.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *           path="/user/password", description="Operations on a user's password.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="POST", summary="Update the current user's password.",
	 *           notes="A valid session is required to change the password through this API.",
	 *           responseClass="Success", nickname="changePassword",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="credentials", description="Data containing name-value pairs for password change.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Password"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $old_password
	 * @param $new_password
	 *
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return bool
	 */
	public static function changePassword( $old_password, $new_password )
	{
		// check valid session,
		// using userId from session, query with check for old password
		// then update with new password
		$userId = Session::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new \Exception( "The user for the current session was not found in the system." );
			}
			// validate answer
			if ( !\CPasswordHelper::verifyPassword( $old_password, $theUser->password ) )
			{
				throw new BadRequestException( "The password supplied does not match." );
			}
			$theUser->setAttribute( 'password', \CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();

			return array( 'success' => true );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @SWG\Api(
	 *       path="/user/profile", description="Operations on a user's profile.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="Retrieve the current user's profile information.",
	 *       notes="This profile, along with password, is the only things that the user can directly change.",
	 *       responseClass="Profile", nickname="getProfile",
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getProfile()
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = Session::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new \Exception( "The user for the current session was not found in the system." );
			}
			// todo protect certain attributes here
			$fields = $theUser->getAttributes(
				array(
					 'first_name',
					 'last_name',
					 'display_name',
					 'email',
					 'phone',
					 'security_question',
					 'default_app_id'
				)
			);

			return $fields;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @SWG\Api(
	 *           path="/user/profile", description="Operations on a user's profile.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *           httpMethod="POST", summary="Update the current user's profile information.",
	 *           notes="Update the security question and answer through this api, as well as, display name, email, etc.",
	 *           responseClass="Success", nickname="changeProfile",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="profile", description="Data containing name-value pairs for the user profile.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Profile"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param array $record
	 *
	 * @throws InternalServerErrorException
	 * @throws \Exception
	 * @return bool
	 */
	public static function changeProfile( $record )
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = Session::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new \Exception( "The user for the current session was not found in the system." );
			}
			$allow = array(
				'first_name',
				'last_name',
				'display_name',
				'email',
				'phone',
				'security_question',
				'security_answer',
				'default_app_id'
			);
			foreach ( $record as $key => $value )
			{
				if ( false === array_search( $key, $allow ) )
				{
					throw new InternalServerErrorException( "Attribute '$key' can not be updated through profile change." );
				}
			}
			$theUser->setAttributes( $record );
			$theUser->save();

			return array( 'success' => true );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update the user profile." );
		}
	}

	/**
	 * @param $email
	 *
	 * @return string
	 */
	protected function _getResetPasswordCode( $email )
	{
		return substr( md5( $email . static::$_randKey ), 0, 10 );
	}

	/**
	 * @param $conf_key
	 *
	 * @return string
	 */
	protected function _makeConfirmationMd5( $conf_key )
	{
		$randNo1 = rand();
		$randNo2 = rand();

		return md5( $conf_key . static::$_randKey . $randNo1 . '' . $randNo2 );
	}
}
