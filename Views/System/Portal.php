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

use DreamFactory\Platform\Resources\BaseSystemRestResource;
use DreamFactory\Platform\Services\SwaggerManager;
use DreamFactory\Platform\Utility\SwaggerUtilities;
use Swagger\Annotations as SWG;

/**
 * Portal
 * DSP portal service
 *
 */
class Portal extends BaseSystemRestResource
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new Portal
	 *
	 * @param \DreamFactory\Platform\Services\BasePlatformService $consumer
	 * @param array                                               $resources
	 */
	public function __construct( $consumer, $resources = array() )
	{
		return parent::__construct(
			$consumer,
			array(
				 'service_name'   => 'system',
				 'name'           => 'Service',
				 'api_name'       => 'service',
				 'type'           => 'System',
				 'description'    => 'System service administration.',
				 'is_active'      => true,
				 'resource_array' => $resources,
			)
		);
	}

	/**
	 * @param mixed $results
	 */
	protected function _postProcess( $results = null )
	{
		if ( static::Get != $this->_action )
		{
			// clear swagger cache upon any portal changes.
			SwaggerManager::clearCache();
		}

		parent::_postProcess( $results );
	}
}
