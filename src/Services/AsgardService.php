<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) SDK For PHP
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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

use DreamFactory\Platform\Components\Counter;
use DreamFactory\Platform\Yii\Models\Stat;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;

/**
 * AsgardService
 * A bag for a DSP stats bundle
 *
 * @property array $apps     Info about apps
 * @property array $app_groups
 * @property array $database Info about the database
 * @property array $users    Info about users
 */
class AsgardService extends SeedBag
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const SYSTEM_TABLE_NAME_PREFIX = 'df_sys_';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array Used to determine if an app is a system app or not
	 */
	protected static $_repoUrls = array(
		'github.com/dreamfactorysoftware/',
		'bitbucket.org/dreamfactory/',
	);

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 */
	public function __construct( $contents = array() )
	{
		//	The structure of the statistics...
		$_contents = array(
			'apps'       => null,
			'app_groups' => null,
			'database'   => null,
			'storage'    => null,
			'roles'      => null,
			'services'   => null,
			'users'      => null,
			'state'      => null,
		);

		parent::__construct( array_merge( $_contents, $contents ) );
	}

	/**
	 * Gather a set of stats for a local DSP
	 *
	 * @return null|bool
	 */
	public static function getStats()
	{
		$_stats = new self();

		//	Connect to the user DSP db
		$_db = Pii::db();
		$_pdo = $_db->getPdoInstance();
		$_dspName = Pii::getParam( 'dsp.name' );

		//	Get the stats for this DSP
		$_stats->set( 'database', $_dbStats = static::_databaseStats( $_dspName, $_db, 'dreamfactory' ) );
		$_stats->set( 'state', $_dbStats['state'] );

		//	Unactivated DSPs don't have these tables
		if ( 'active' == $_dbStats['state'] )
		{
			$_stats->set( 'apps', static::_appStats( $_dspName, $_pdo ) );

			$_stats->set(
				'app_groups',
				array(
					'counts' => static::_rowCount( 'df_sys_app_group', $_dspName, $_pdo )
				)
			);

			$_stats->set(
				'roles',
				array(
					'counts' => static::_rowCount( 'df_sys_role', $_dspName, $_pdo )
				)
			);

			$_stats->set(
				'services',
				array(
					'counts' => static::_rowCount( 'df_sys_service', $_dspName, $_pdo )
				)
			);

			$_stats->set(
				'users',
				array(
					'counts' => static::_rowCount( 'df_sys_user', $_dspName, $_pdo )
				)
			);
		}

		//	Log it to the database...
		Stat::create( Stat::TYPE_ASGARD, Pii::user()->getId(), $_stats->contents() );

		return $_stats->contents();
	}

	/**
	 * @param string         $dspName
	 * @param \CDbConnection $db
	 * @param string         $dbName The name of the database
	 *
	 * @return bool
	 */
	protected static function _databaseStats( $dspName, $db, $dbName )
	{
		$_details = array();
		$_tableCounter = new Counter( 'system' );
		$_rowCounter = new Counter( 'system' );
		$_len = strlen( static::SYSTEM_TABLE_NAME_PREFIX );
		$_pdo = $db->getPdoInstance();

		$_schema = $db->getSchema();
		$_tables = $_schema->getTableNames();

		if ( empty( $_tables ) || 1 == sizeof( $_tables ) )
		{
			//	Only a cache table means unactivated DSP
			if ( 1 == sizeof( $_tables ) )
			{
				$_tableCounter->increment( 'system' );
			}

			return array(
				'table_counts' => $_tableCounter->getCounters(),
				'row_counts'   => $_rowCounter->getCounters(),
				'details'      => $_details,
				'state'        => 'inactive',
			);
		}

		/**
		 * Table stats
		 */

		/** @var $_tables \CDbTableSchema[] */
		foreach ( $_tables as $_table )
		{
			$_system = false;

			if ( static::SYSTEM_TABLE_NAME_PREFIX == substr( $_table, 0, $_len ) )
			{
				$_system = true;
			}

			$_tableCounter->increment( $_system ? '*' : null );
			$_rowCounter->increment( $_system ? '*' : null, static::_rowCount( $_table, $dspName, $_pdo ) );

			unset( $_tableSchema, $_tableName );
		}

		unset( $_tables );

		/**
		 * DB Stats
		 */
		$_sql = <<<MYSQL
SELECT
	SUM( data_length + index_length ) / 1024 / 1024 AS mb_used_nbr,
	SUM( data_free ) / 1024 / 1024 AS mb_free_nbr
FROM
	information_schema.TABLES
WHERE
	table_schema = :table_schema
GROUP BY
	table_schema
MYSQL;

		if ( false !== ( $_row = Sql::find( $_sql, array( ':table_schema' => $dbName ), Pii::pdo() ) ) )
		{
			$_details['disk_space_used'] = $_row['mb_used_nbr'];
			$_details['disk_space_free'] = $_row['mb_free_nbr'];
		}

		return array(
			'table_counts' => $_tableCounter->getCounters(),
			'row_counts'   => $_rowCounter->getCounters(),
			'size_counts'  => $_details,
			'state'        => 'active',
		);
	}

	/**
	 * @param string $dspName
	 * @param \PDO   $pdo
	 *
	 * @return bool
	 */
	protected static function _appStats( $dspName, $pdo )
	{
		Sql::setConnection( $pdo );

		$_sql = <<<MYSQL
SELECT
	a.api_name,
	a.is_active,
	a.url,
	a.is_url_external,
	a.import_url
FROM
	df_sys_app a
ORDER BY
	a.api_name
MYSQL;

		if ( false === ( $_reader = Sql::query( $_sql ) ) )
		{
			Log::error( 'Error retrieving app information from DSP "' . $dspName . '":' . print_r( $pdo->errorInfo(), true ) );

			return false;
		}

		$_details = array();
		$_counter = new Counter( 'system' );

		foreach ( $_reader as $_row )
		{
			$_system = false;

			foreach ( static::$_repoUrls as $_repo )
			{
				if ( false !== strpos( $_row['import_url'], $_repo ) )
				{
					$_system = true;
					break;
				}

				unset( $_repo );
			}

			$_details[$_row['api_name']] = array(
				'apiName'      => $_row['api_name'],
				'active'       => $_row['is_active'] == 1 ? true : false,
				'url'          => $_row['url'],
				'external_url' => $_row['is_url_external'] == 1 ? true : false,
				'import_url'   => $_row['import_url'],
				'system'       => $_system,
			);

			$_counter->increment( $_system ? '*' : null );

			unset( $_row );
		}

		unset( $_reader );

		return array(
			'counts'  => $_counter->getCounters(),
			'details' => $_details,
		);
	}

	/**
	 * @param string $tableName
	 * @param string $dspName
	 * @param \PDO   $pdo
	 *
	 * @return bool
	 */
	protected static function _rowCount( $tableName, $dspName, \PDO $pdo )
	{
		Sql::setConnection( $pdo );

		$_sql = <<<MYSQL
SELECT
	COUNT(*)
FROM
	{$tableName}
MYSQL;

		if ( false === ( $_count = Sql::scalar( $_sql ) ) )
		{
			Log::error( 'Error retrieving information from DSP "' . $dspName . '.' . $tableName . '":' . print_r( $pdo->errorInfo(), true ) );

			return false;
		}

		return $_count;
	}
}