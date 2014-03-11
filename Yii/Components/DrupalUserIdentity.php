<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) SDK For PHP
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
namespace DreamFactory\Platform\Yii\Components;

use DreamFactory\Platform\Utility\Drupal;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * DrupalUserIdentity
 * Provides Drupal authentication services
 */
class DrupalUserIdentity extends \CUserIdentity
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const Authenticated = 0;
	/**
	 * @var int
	 */
	const InvalidCredentials = 1;

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var int Our user id
	 */
	protected $_drupalId;
	/**
	 * @var \User
	 */
	protected $_user = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Authenticates a user.
	 *
	 * @return boolean
	 */
	public function authenticate()
	{
		/** @var \stdClass $_user */
		if ( false === ( $_user = Drupal::authenticateUser( $this->username, $this->password ) ) || !is_object( $_user ) || !$_user->success )
		{
			$this->errorCode = self::ERROR_USERNAME_INVALID;

			return false;
		}

		if ( !isset( $_user->drupal_id ) )
		{
			if ( is_string( $_user->success ) )
			{
				$_user->success = ( 'false' != $_user->success );
			}

			if ( !$_user->success )
			{
				Log::error( 'Drupal user login of "' . $this->username . '" failed.' );

				return false;
			}
			else
			{
				Log::warning( 'Uncommon response from Drupal::authenticateUser(): ' . print_r( $_user, true ) );
			}
		}

		$this->_user = $_user;
		$this->_drupalId = Option::get( $_user, 'drupal_id' );

		$this->setState( 'email', $this->username );
		$this->setState( 'first_name', Option::get( $_user, 'first_name', $this->username ) );
		$this->setState( 'last_name', Option::get( $_user, 'last_name', $this->username ) );
		$this->setState( 'display_name', Option::get( $_user, 'display_name', $this->username ) );
		$this->setState( 'password', $this->password );
		$this->setState( 'df_authenticated', true );

		$this->errorCode = self::ERROR_NONE;

		Log::debug( 'Drupal user auth: ' . $this->username );

		return true;
	}

	/**
	 * Returns the user's ID instead of the name
	 *
	 * @return int|string
	 */
	public function getId()
	{
		return $this->_drupalId;
	}

	/**
	 * @param int $drupalId
	 *
	 * @return DrupalUserIdentity
	 */
	public function setUserId( $drupalId )
	{
		$this->_drupalId = $drupalId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->_drupalId;
	}

	/**
	 * @return int
	 */
	public function getDrupalId()
	{
		return $this->_drupalId;
	}

	/**
	 * @return \User
	 */
	public function getUser()
	{
		return $this->_user;
	}
}
