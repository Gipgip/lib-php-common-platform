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
namespace DreamFactory\Platform\Enums;

use Kisma\Core\Enums\SeedEnum;

/**
 * PlatformStorageDrivers
 * Storage driver string constants
 */
class PlatformStorageDrivers extends SeedEnum
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const MS_SQL = 'mssql';
	/**
	 * @var string
	 */
	const SYBASE = 'dblib';
	/**
	 * @var string
	 */
	const SQL_SERVER = 'sqlsrv';
	/**
	 * @var string
	 */
	const MYSQL = 'mysql';
	/**
	 * @var string
	 */
	const MYSQLI = 'mysqli';
	/**
	 * @var string
	 */
	const SQLITE = 'sqlite';
	/**
	 * @var string
	 */
	const SQLITE2 = 'sqlite2';
	/**
	 * @var string
	 */
	const ORACLE = 'oci';
	/**
	 * @var string
	 */
	const POSTGRESQL = 'pgsql';
}
