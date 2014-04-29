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
namespace DreamFactory\Platform\Components;

use DreamFactory\Platform\Utility\Platform;
use Kisma\Core\Components\Flexistore;
use Kisma\Core\Enums\CacheTypes;

/**
 * A simple store class that use's the Kisma store
 */
class PlatformStore extends Flexistore
{
    //*************************************************************************
    //	Constants
    //*************************************************************************

    /**
     * @type string
     */
    const DEFAULT_NAMESPACE = 'df.platform';
    /**
     * @type string
     */
    const STORE_CACHE_PATH = '/store.cache';
    /**
     * @type string The session key
     */
    const PERSISTENT_STORAGE_KEY = 'df.session_key';

    //*************************************************************************
    //	Methods
    //*************************************************************************

    /**
     * Constructor.
     *
     * @param array|string $type
     * @param array        $data An array of key value pairs with which to initialize storage
     */
    public function __construct( $type = CacheTypes::FILE_SYSTEM, array $data = array() )
    {
        parent::__construct(
            $type,
            array(
                'namespace' => static::DEFAULT_NAMESPACE,
                'arguments' => array(Platform::getPrivatePath( static::STORE_CACHE_PATH ), '.dfcc')
            ),
            false
        );

        //  Load it up
        foreach ( $data as $_key => $_value )
        {
            $this->_store->save( $_key, $_value );
        }
    }

    /**
     * @return string
     */
    public static function buildCacheKey()
    {
        $_key = PHP_SAPI . '.' . Option::server( 'REMOTE_ADDR', gethostname() ) . '.' . Option::server( 'HTTP_HOST', gethostname() );

        return $_key;
    }
}
