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
namespace DreamFactory\Platform\Resources\System;

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use DreamFactory\Platform\Resources\BaseSystemRestResource;
use DreamFactory\Platform\Services\BasePlatformService;
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;

/**
 * Config
 * DSP system administration manager
 *
 */
class Config extends BaseSystemRestResource
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Constructor
	 *
	 * @param BasePlatformService $consumer
	 * @param array               $resourceArray
	 *
	 * @return Config
	 */
	public function __construct( $consumer = null, $resourceArray = array() )
	{
		parent::__construct(
			$consumer,
			array(
				 'name'           => 'Configuration',
				 'type'           => 'System',
				 'service_name'   => 'system',
				 'type_id'        => PlatformServiceTypes::SYSTEM_SERVICE,
				 'api_name'       => 'config',
				 'description'    => 'Service general configuration',
				 'is_active'      => true,
				 'resource_array' => $resourceArray,
				 'verb_aliases'   => array(
					 static::Patch => static::Post,
					 static::Merge => static::Post,
				 )
			)
		);
	}

	/**
	 * Override for GET of public info
	 *
	 * @param string $operation
	 * @param null   $resource
	 *
	 * @return bool
	 */
	public function checkPermission( $operation, $resource = null )
	{
		if ( 'read' == $operation )
		{
			return true;
		}

		return ResourceStore::checkPermission( $operation, $this->_serviceName, $resource );
	}

	/**
	 * {@InheritDoc}
	 * @todo Currently allow everyone to query config, long term this needs to hide certain fields
	 */
	protected function _handleGet()
	{
//		UserManager::checkSessionPermission( 'read', 'system', 'config' );

		$_response = parent::_handleGet();

		if ( $this->_includeSchema)
			R
			if ( $include_schema )
			{
				$results['meta']['schema'] = SqlDbUtilities::describeTable(
					Pii::db(),
					$model->tableName(),
					SystemManager::SYSTEM_TABLE_PREFIX
				);
			}

			// get current and latest version info
			$_dspVersion = SystemManager::getCurrentVersion();
			$results['dsp_version'] = $_dspVersion;
			if ( !\Fabric::fabricHosted() )
			{
				$_latestVersion = SystemManager::getLatestVersion();
				$results['latest_version'] = $_latestVersion;
				$results['upgrade_available'] = version_compare( $_dspVersion, $_latestVersion, '<' );
			}

			// get cors data from config file
			$results['allowed_hosts'] = SystemManager::getAllowedHosts();

			return $results;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error retrieving configuration record.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	protected function _postProcess()
	{
		//	Only return a single row, not in an array
		if ( null !== ( $_record = Option::getDeep( $this->_response, 'record', 0 ) ) )
		{
			if ( 1 == sizeof( $this->_response['record'] ) )
			{
				$this->_response = $_record;
			}
		}
		else if ( is_array( $this->_response ) && isset( $this->_response[0] ) && sizeof( $this->_response ) == 1 )
		{
			$this->_response = $this->_response[0];
		}

		$this->_response['dsp_version'] = defined( 'DSP_VERSION' ) ? DSP_VERSION : 'Unknown';
		$this->_response['allow_remote_logins'] = ( Pii::getParam( 'dsp.allow_remote_logins', false ) && $this->_response['allow_open_registration'] );

		if ( false !== $this->_response['allow_remote_logins'] )
		{
			$this->_response['allow_admin_remote_logins'] = Pii::getParam( 'dsp.allow_admin_remote_logins', false );

			$_rows = Sql::findAll( 'SELECT id, api_name, provider_name FROM df_sys_provider ORDER BY 1', array(), Pii::pdo() );

			if ( !empty( $_rows ) )
			{
				$this->_response['remote_login_providers'] = array();

				foreach ( $_rows as $_row )
				{
					$this->_response['remote_login_providers'][] = $_row['api_name'];
				}
			}
			else
			{
				//	No providers, no remote logins
				$this->_response['allow_remote_logins'] = false;
				$this->_response['allow_admin_remote_logins'] = false;
			}
		}

		parent::_postProcess();
	}
}
