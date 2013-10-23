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
use Kisma\Core\Utility\Option;

$_base = require( __DIR__ . '/BasePlatformRestSvc.swagger.php' );

$_app = require( __DIR__ . '/../Resources/System/App.swagger.php' );
$_appGroup = require( __DIR__ . '/../Resources/System/AppGroup.swagger.php' );
$_config = require( __DIR__ . '/../Resources/System/Config.swagger.php' );
$_constant = require( __DIR__ . '/../Resources/System/Constant.swagger.php' );
//$_custom = require( __DIR__ . '/../Resources/System/CustomSettings.swagger.php' );
$_email = require( __DIR__ . '/../Resources/System/EmailTemplate.swagger.php' );
$_role = require( __DIR__ . '/../Resources/System/Role.swagger.php' );
$_service = require( __DIR__ . '/../Resources/System/Service.swagger.php' );
$_user = require( __DIR__ . '/../Resources/System/User.swagger.php' );

$_base['apis'] = array_merge(
	array(
		 array(
			 'path'        => '/{api_name}',
			 'operations'  =>
			 array(
				 0 =>
				 array(
					 'method'   => 'GET',
					 'summary'  => 'getResources() - List resources available for system management.',
					 'nickname' => 'getResources',
					 'type'     => 'Resources',
					 'notes'    => 'See listed operations for each resource available.',
				 ),
			 ),
			 'description' => 'Operations available for system management.',
		 ),
	),
	Option::get( $_app, 'apis' ),
	Option::get( $_appGroup, 'apis' ),
	Option::get( $_config, 'apis' ),
	Option::get( $_constant, 'apis' ),
//	Option::get( $_custom, 'apis' ),
	Option::get( $_email, 'apis' ),
	Option::get( $_role, 'apis' ),
	Option::get( $_service, 'apis' ),
	Option::get( $_user, 'apis' )
);

$_base['models'] = array_merge(
	Option::get( $_app, 'models' ),
	Option::get( $_appGroup, 'models' ),
	Option::get( $_config, 'models' ),
	Option::get( $_constant, 'models' ),
//	Option::get( $_custom, 'models' ),
	Option::get( $_email, 'models' ),
	Option::get( $_role, 'models' ),
	Option::get( $_service, 'models' ),
	Option::get( $_user, 'models' )
);

return $_base;