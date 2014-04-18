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

$_base = require( __DIR__ . '/BaseDbSvc.swagger.php' );

$_base['apis'] = array(
    array(
        'path'        => '/{api_name}',
        'operations'  => array(
            array(
                'method'           => 'GET',
                'summary'          => 'getResources() - List all resources.',
                'nickname'         => 'getResources',
                'type'             => 'Resources',
                'event_name'       => '{api_name}.list',
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            => 'List the names of the available tables in this storage. ',
            ),
            array(
                'method'           => 'GET',
                'summary'          => 'getTables() - List all properties on given tables.',
                'nickname'         => 'getTables',
                'type'             => 'Tables',
                'event_name'       => 'tables.describe',
                'parameters'       => array(
                    array(
                        'name'          => 'names',
                        'description'   => 'Comma-delimited list of the table names to retrieve.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => true,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            => 'List the properties of the given tables in this storage.',
            ),
        ),
        'description' => 'Operations available for database tables.',
    ),
    array(
        'path'        => '/{api_name}/{table_name}',
        'operations'  => array(
            array(
                'method'           => 'GET',
                'summary'          => 'getRecords() - Retrieve one or more records.',
                'nickname'         => 'getRecords',
                'type'             => 'RecordsResponse',
                'event_name'       => 'table.records.get',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'ids',
                        'description'   => 'Comma-delimited list of the identifiers of the resources to retrieve.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'filter',
                        'description'   => 'SQL-like filter to limit the resources to retrieve.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'limit',
                        'description'   => 'Set to limit the filter results.',
                        'allowMultiple' => false,
                        'type'          => 'integer',
                        'format'        => 'int32',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'offset',
                        'description'   => 'Set to offset the filter results to a particular record count.',
                        'allowMultiple' => false,
                        'type'          => 'integer',
                        'format'        => 'int32',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'order',
                        'description'   => 'SQL-like order containing field and direction for filter results.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'include_count',
                        'description'   => 'Include the total number of filter results as meta data.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'include_schema',
                        'description'   => 'Include the table schema as meta data.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'continue',
                        'description'   =>
                            'In batch scenarios, where supported, continue processing even after one record fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Use the \'ids\' or \'filter\' parameter to limit resources that are returned. ' .
                    'Use the \'fields\' parameter to limit properties returned for each resource. ' .
                    'By default, all fields are returned for all resources. ' .
                    'Use the \'related\' parameter to return related records for each resource. ' .
                    'By default, no related records are returned. ' .
                    'Alternatively, to send the \'ids\' or a \'filter\' with or without \'params\' as posted data ' .
                    'use the POST request with X-HTTP-METHOD = GET header and post array of ids or a filter with params.',
            ),
            array(
                'method'           => 'POST',
                'summary'          => 'createRecords() - Create one or more records.',
                'nickname'         => 'createRecords',
                'type'             => 'RecordsResponse',
                'event_name'       => 'table.records.create',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'body',
                        'description'   => 'Data containing name-value pairs of records to create.',
                        'allowMultiple' => false,
                        'type'          => 'RecordsRequest',
                        'paramType'     => 'body',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'continue',
                        'description'   =>
                            'In batch scenarios, where supported, continue processing even after one record fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'rollback',
                        'description'   =>
                            'In batch scenarios, where supported, rollback all changes if any record of the batch fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure, leaving any changes.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'X-HTTP-METHOD',
                        'description'   => 'Override request using POST to tunnel other http request, such as DELETE.',
                        'enum'          => array( 'GET', 'PUT', 'PATCH', 'DELETE' ),
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'header',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Post data should be a single record or an array of records (shown). ' .
                    'By default, only the id property of the record is returned on success. ' .
                    'Use \'fields\' parameter to return more info.',
            ),
            array(
                'method'           => 'PATCH',
                'summary'          => 'updateRecords() - Update (patch) one or more records.',
                'nickname'         => 'updateRecords',
                'type'             => 'RecordsResponse',
                'event_name'       => 'table.records.update',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'body',
                        'description'   => 'Data containing name-value pairs of records to update.',
                        'allowMultiple' => false,
                        'type'          => 'RecordsRequest',
                        'paramType'     => 'body',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'ids',
                        'description'   => 'Comma-delimited list of the identifiers of the resources to modify.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'filter',
                        'description'   => 'SQL-like filter to limit the resources to modify.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'continue',
                        'description'   =>
                            'In batch scenarios, where supported, continue processing even after one record fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'rollback',
                        'description'   =>
                            'In batch scenarios, where supported, rollback all changes if any record of the batch fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure, leaving any changes.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Post data should be a single record or an array of records (shown). ' .
                    'By default, only the id property of the record is returned on success. ' .
                    'Use \'fields\' parameter to return more info.',
            ),
            array(
                'method'           => 'DELETE',
                'summary'          => 'deleteRecords() - Delete one or more records.',
                'nickname'         => 'deleteRecords',
                'type'             => 'RecordsResponse',
                'event_name'       => 'table.records.delete',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'ids',
                        'description'   => 'Comma-delimited list of the identifiers of the resources to delete.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'filter',
                        'description'   => 'SQL-like filter to limit the resources to delete.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'force',
                        'description'   => 'Set force to true to delete all records in this table, otherwise \'ids\' parameter is required.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                        'default'       => false,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'continue',
                        'description'   =>
                            'In batch scenarios, where supported, continue processing even after one record fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'rollback',
                        'description'   =>
                            'In batch scenarios, where supported, rollback all changes if any record of the batch fails. ' .
                            'Default behavior is to halt and return results up to the first point of failure, leaving any changes.',
                        'allowMultiple' => false,
                        'type'          => 'boolean',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Use \'ids\' or filter to delete specific records, otherwise set \'force\' to true to clear the table. ' .
                    'By default, only the id property of the record is returned on success, use \'fields\' to return more info. ' .
                    'Alternatively, to delete by records, a complicated filter, or a large list of ids, ' .
                    'use the POST request with X-HTTP-METHOD = DELETE header and post array of records, filter, or ids.',
            ),
        ),
        'description' => 'Operations for table records administration.',
    ),
    array(
        'path'        => '/{api_name}/{table_name}/{id}',
        'operations'  => array(
            array(
                'method'           => 'GET',
                'summary'          => 'getRecord() - Retrieve one record by identifier.',
                'nickname'         => 'getRecord',
                'type'             => 'RecordResponse',
                'event_name'       => 'table.record.get',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'id',
                        'description'   => 'Identifier of the resource to retrieve.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for the record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table or record does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Use the \'fields\' parameter to limit properties that are returned. ' .
                    'By default, all fields are returned. ' .
                    'Use the \'related\' parameter to return related records. ' .
                    'By default, no related records are returned.',
            ),
            array(
                'method'           => 'POST',
                'summary'          => 'createRecord() - Create one record with given identifier.',
                'nickname'         => 'createRecord',
                'type'             => 'RecordResponse',
                'event_name'       => 'table.record.create',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'id',
                        'description'   => 'Identifier of the resource to create.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'body',
                        'description'   => 'Data containing name-value pairs of the record to create.',
                        'allowMultiple' => false,
                        'type'          => 'RecordRequest',
                        'paramType'     => 'body',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Post data should be an array of fields for a single record. ' .
                    'Use the \'fields\' parameter to return more properties. By default, the id is returned.',
            ),
            array(
                'method'           => 'PATCH',
                'summary'          => 'updateRecord() - Update (patch) one record by identifier.',
                'nickname'         => 'updateRecord',
                'type'             => 'RecordResponse',
                'event_name'       => 'table.record.update',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'The name of the table you want to update.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'id',
                        'description'   => 'Identifier of the resource to update.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'body',
                        'description'   => 'Data containing name-value pairs of the fields to update.',
                        'allowMultiple' => false,
                        'type'          => 'RecordRequest',
                        'paramType'     => 'body',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table or record does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            =>
                    'Post data should be an array of fields for a single record. ' .
                    'Use the \'fields\' parameter to return more properties. By default, the id is returned.',
            ),
            array(
                'method'           => 'DELETE',
                'summary'          => 'deleteRecord() - Delete one record by identifier.',
                'nickname'         => 'deleteRecord',
                'type'             => 'RecordResponse',
                'event_name'       => 'table.record.delete',
                'parameters'       => array(
                    array(
                        'name'          => 'table_name',
                        'description'   => 'Name of the table to perform operations on.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'id',
                        'description'   => 'Identifier of the resource to delete.',
                        'allowMultiple' => false,
                        'type'          => 'string',
                        'paramType'     => 'path',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'fields',
                        'description'   => 'Comma-delimited list of field names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'related',
                        'description'   => 'Comma-delimited list of relationship names to retrieve for each record.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_field',
                        'description'   =>
                            'Single or comma-delimited list of the fields used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                    array(
                        'name'          => 'id_type',
                        'description'   =>
                            'Single or comma-delimited list of the field types used as identifiers for the table, ' .
                            'used to override defaults or provide identifiers when none are provisioned.',
                        'allowMultiple' => true,
                        'type'          => 'string',
                        'paramType'     => 'query',
                        'required'      => false,
                    ),
                ),
                'responseMessages' => array(
                    array(
                        'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                        'code'    => 400,
                    ),
                    array(
                        'message' => 'Unauthorized Access - No currently valid session available.',
                        'code'    => 401,
                    ),
                    array(
                        'message' => 'Not Found - Requested table or record does not exist.',
                        'code'    => 404,
                    ),
                    array(
                        'message' => 'System Error - Specific reason is included in the error message.',
                        'code'    => 500,
                    ),
                ),
                'notes'            => 'Use the \'fields\' parameter to return more deleted properties. By default, the id is returned.',
            ),
        ),
        'description' => 'Operations for single record administration.',
    ),
);

return $_base;
