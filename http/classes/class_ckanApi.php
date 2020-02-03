<?php
/**
 * Copyright (c) 2010 Jeffrey Barke <http://jeffreybarke.net/>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * Based on following software:
 * A PHP client for the CKAN (Comprehensive Knowledge Archive Network) API.
 *
 * For details and documentation, please see http://github.com/jeffreybarke/Ckan_client-PHP
 * @author		Jeffrey Barke
 * @copyright	Copyright 2010 Jeffrey Barke
 * @license		http://github.com/jeffreybarke/Ckan_client-PHP/blob/master/LICENSE
 * @link		http://github.com/jeffreybarke/Ckan_client-PHP
 *
 */

require_once(dirname(__FILE__) . "/../../core/globalSettings.php");

class ckanApi
{

	// Properties ---------------------------------------------------------

	/**
	 * Client's API key. Required for any PUT or POST methods.
	 *
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#ckan-api-keys
	 */
	public $api_key = FALSE;

	/**
	 * Servers Host IP. Required for any PUT or POST methods.
	 *
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#ckan-api-keys
	 */
	public $host_name = FALSE;

	/**
	 * Version of the CKAN API we're using.
	 *
	 * @var		string
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#api-versions
	 */
	public $api_version = '2';

	/**
	 * URI to the CKAN web service.
	 *
	 * @var		string
	 */
	public $base_url = 'http://ckan.net/api/%d/';

	/**
	 * Internal cURL object.
	 *
	 */
	private $ch = FALSE;

	/**
	 * cURL headers.
	 *
	 */
	private $ch_headers;

	/**
	 * Standard HTTP status codes.
	 *
	 * @var		array
	 */
	private $http_status_codes = array(
		'200' => 'OK',
		'201' => 'OK - package created successfully!',
		'301' => 'Moved Permanently',
		'400' => 'Bad Request',
		'403' => 'Not Authorized',
		'404' => 'Not Found',
		'409' => 'Conflict (e.g. name already exists)',
		'500' => 'Service Error'
	);

	/**
	 * Array of CKAN resources and their URI fragment.
	 *
	 * @var		array
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#ckan-model-api
	 */
	private $resources = array(
		'package_register' => 'rest/package',
		'package_entity' => 'rest/package',
		'group_register' => 'rest/group',
		'group_entity' => 'rest/group',
		'tag_register' => 'rest/tag',
		'tag_entity' => 'rest/tag',
		'rating_register' => 'rest/rating',
		'rating_entity' => 'rest/rating',
		'revision_register' => 'rest/revision',
		'revision_entity' => 'rest/revision',
		'license_list' => 'rest/licenses',
		'package_search' => 'search/package'
	);

	/**
	 * Array of CKAN resources and their URI fragment.
	 *
	 * @var		array
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#ckan-model-api
	 */
	private $actionList = array(
		'package_create'  => 'action/package_create',
		'package_show' => 'action/package_show',
		'package_update' => 'action/package_update',
		'package_delete' => 'action/package_delete',
                'package_search' => 'action/package_search',
		'resource_create' => 'action/resource_create',
		'resource_show' => 'action/resource_show',
		'resource_update' => 'action/resource_update',
		'resource_delete' => 'action/resource_delete',
		'resource_view_show' => 'action/resource_view_show',
		'resource_view_create' => 'action/resource_view_create',
		'resource_view_update' => 'action/resource_view_update',
		'resource_view_list' => 'action/resource_view_list',
		'resource_view_delete' => 'action/resource_view_delete',
		'organization_create' => 'action/organization_create',
		'organization_show' => 'action/organization_show',
		'organization_update' => 'action/organization_update',
		'organization_delete' => 'action/organization_delete',
		'organization_list_for_user' => 'action/organization_list_for_user',
		'organization_list' => 'action/organization_list',
		'organization_purge' => 'action/organization_purge',
		'organization_revision_list' => 'action/organization_revision_list',
		'user_create' => 'action/user_create',
		'user_show' => 'action/user_show',
		'user_update' => 'action/user_update',
		'group_create' => 'action/group_create',
		'group_update' => 'action/group_update',
		'group_delete' => 'action/group_delete',
		'group_show' => 'action/group_show',
		'group_package_show' => 'action/group_package_show',
		'member_create' => 'action/member_create',
		'member_delete' => 'action/member_delete',
		'member_list' => 'action/member_list',
		'roles_show' => 'action/roles_show',
		'package_owner_org_update' => 'package_owner_org_update',
		'group_list_authz' => 'action/group_list_authz'
	);

	/**
	 * ckanApi user agent string.
	 *
	 * @var		string

	 */
	private $user_agent = CONNECTION_USERAGENT;

	// Magic methods ------------------------------------------------------

	/**
	 * Constructor
	 *
	 * Calls the API key, base URI and user agent setters.
	 * Initializes the internal cURL object.
	 *
	 * @param	string	CKAN API key.
	 */
	public function __construct($api_key = FALSE,$host_name = FALSE)
	{
		// If provided, set the API key.
		if ($api_key)
		{
			$this->set_api_key($api_key);
		}
		// If provided, set the Name of host to call - needed for decide if proxy should be used.
		if ($host_name)
		{
			//$e = new mb_exception($host_name);
			$this->set_host_name($host_name);
		}
		// Set base URI and Ckan_client user agent string.
		$this->set_base_url();
		$this->set_user_agent();
		// Create cURL object.
		$this->ch = curl_init();
		//$e = new mb_exception($this->host_name);
	    	$NOT_PROXY_HOSTS_array = explode(",", NOT_PROXY_HOSTS);

		if(CONNECTION_PROXY != "" AND (in_array($this->host_name, $NOT_PROXY_HOSTS_array)!= true)){
			curl_setopt($this->ch, CURLOPT_PROXY,CONNECTION_PROXY.":".CONNECTION_PORT);
			$e = new mb_notice("class_ckanApi.php: Proxy will be used!");
			if(CONNECTION_PASSWORD != ""){
				curl_setopt ($this->ch, CURLOPT_PROXYUSERPWD, CONNECTION_USER.':'.CONNECTION_PASSWORD);	
			}
		} else {
			$e = new mb_notice("class_ckanApi.php: Proxy will not be used!");
		}	
		// Follow any Location: headers that the server sends.
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		// However, don't follow more than five Location: headers.
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		// Automatically set the Referer: field in requests 
		// following a Location: redirect.
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
		// Return the transfer as a string instead of dumping to screen. 
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		// If it takes more than 45 seconds, fail
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 45);
		// We don't want the header (use curl_getinfo())
		curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
		// Set user agent to Ckan_client
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
		// Track the handle's request string
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, TRUE);
		// Attempt to retrieve the modification date of the remote document.
		curl_setopt($this->ch, CURLOPT_FILETIME, TRUE);
		// Initialize cURL headers
		$this->set_headers();
		// Include PHP Markdown library
		//require_once('lib/php_markdown/markdown.php');
	}

	//for debugging purposes only
	private function logit($text){
	 	if($h = fopen("/tmp/class_ckanApi.log","a")){
					$content = $text .chr(13).chr(10);
					if(!fwrite($h,$content)){
						#exit;
					}
					fclose($h);
				}
	 	
	 }

	/**
	 * Destructor
	 *
	 * Since it's possible to leave cURL open, this is the last chance to
	 * close it.
	 */
	public function __destruct()
	{
		// Cleanup
		if ($this->ch)
		{
			curl_close($this->ch);
			unset($this->ch);
		}
	}

	// Setters ------------------------------------------------------------

	/**
	 * Sets the CKAN API key.
	 *
	 * @access	public
	 * @param	string	CKAN API key.
	 * @return	void
	 */
	public function set_api_key($api_key)
	{
		$this->api_key = $api_key;
	}

	/**
	 * Sets the Host IP.
	 *
	 * @access	public
	 * @param	string	HOST IP / Name.
	 * @return	void
	 */
	public function set_host_name($host_name)
	{
		$this->host_name = $host_name;
	}

	/**
	 * Sets the CKAN API base URI.
	 *
	 * @access	private
	 * @return	void
	 */
	public function set_base_url()
	{
		// Append the CKAN API version to the base URI.
		$this->base_url = sprintf($this->base_url, $this->api_version);
	}

	/**
	 * Sets the custom cURL headers.
	 *
	 * @access	private
	 * @return	void
	 */
	private function set_headers()
	{
		$date = new DateTime(NULL, new DateTimeZone('UTC'));
		$this->ch_headers = array(
			'Date: ' . $date->format('D, d M Y H:i:s') . ' GMT', // RFC 1123
			'Accept: application/json;q=1.0, application/xml;q=0.5, */*;q=0.0',
			'Accept-Charset: utf-8',
			'Accept-Encoding: gzip',
			'content-type: application/json'
		);
	}

	/**
	 * Sets the Ckan_client user agent string.
	 *
	 * @access	private
	 * @return	void
	 */
	private function set_user_agent()
	{
		if ('80' === @$_SERVER['SERVER_PORT'])
		{
			$server_name = 'http://' . $_SERVER['SERVER_NAME'];
		}
		else
		{
			$server_name = '';
		}
		$this->user_agent = sprintf($this->user_agent, $this->version) . 
			' (' . $server_name . $_SERVER['PHP_SELF'] . ')';
	}

	// Public action API methods -----------------------------------------------
	
	// package show 

	/**
	 * @access	public
	 * @param  	string  id or name of the dataset
	 * @return	the dataset as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.get.package_show
	 */
	public function action_package_show($id)
	{
		return $this->make_request('POST', 
			$this->actionList['package_show'], 
			$id);
	}
	// package create 

	/**
	 * @access	public
	 * @param  	string  package
	 * @return	the newly created dataset as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.package_create
	 */
	public function action_package_create($data)
	{
		return $this->make_request('POST', 
			$this->actionList['package_create'], 
			$data);
	}
	

	// package update

	/**
	 * @access	public
	 * @param  	string  package
	 * @return	the updated dataset as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.update.package_update
	 */
	public function action_package_update($data)
	{
		return $this->make_request('POST', 
			$this->actionList['package_update'], 
			$data);
	}
	

	// package delete

	/**
	 * @access	public
	 * @param  	string  id
	 * @return	?
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.delete.package_delete
	 */
	public function action_package_delete($id)
	{
		return $this->make_request('POST', 
			$this->actionList['package_delete'], 
			$id);
	}

	// package search

	/**
	 * @access	public
	 * @param  	string query (q and qf parameters)
	 * @return	?
	 * @link	http://docs.ckan.org/en/latest/api/#ckan.logic.action.get.package_search
	 */
	public function action_package_search($query)
	{
		return $this->make_request('POST', 
			$this->actionList['package_search'], 
			$query);
	}

	// user create 

	/**
	 * @access	public
	 * @param  	
	 * @return	the newly created user
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.package_create
	 */
	public function action_user_create($data)
	{
		return $this->make_request('POST', 
			$this->actionList['user_create'], 
			$data);
	}

	// user show

	/**
	 * @access	public
	 * @param  	
	 * @return	the newly created user
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.package_create
	 */
	public function action_user_show($data)
	{
		return $this->make_request('POST', 
			$this->actionList['user_show'], 
			$data);
	}

	// user update

	/**
	 * @access	public
	 * @param  	
	 * @return	the user to update
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.package_create
	 */
	public function action_user_update($data)
	{
		return $this->make_request('POST', 
			$this->actionList['user_update'], 
			$data);
	}


	// group create 

	/**
	 * @access	public
	 * @param  	string  group
	 * @return	the newly created group as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.group_create
	 */

	public function action_group_create($data)
	{
		return $this->make_request('POST', 
			$this->actionList['group_create'], 
			$data);
	}

	// group show

	/**
	 * @access	public
	 * @param  	string  group
	 * @return	the newly created group as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.group_create
	 */
	public function action_group_show($id)
	{
		return $this->make_request('POST', 
			$this->actionList['group_show'], 
			$id);
	}

	// group delete

	/**
	 * @access	public
	 * @param  	string  id
	 * @return	the newly created group as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.create.group_create
	 */
	public function action_group_delete($id)
	{
		return $this->make_request('POST', 
			$this->actionList['group_delete'], 
			$id);
	}

	// group update

	/**
	 * @access	public
	 * @param  	string  group
	 * @return	the updated group as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.update.package_update
	 */
	public function action_group_update($data)
	{
		return $this->make_request('POST', 
			$this->actionList['group_update'], 
			$data);
	}

	// member list

	/**
	 * @access	public
	 * @param  	
	 * @return	the member as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.update.package_update
	 */
	public function action_member_list($data)
	{
		return $this->make_request('POST', 
			$this->actionList['member_list'], 
			$data);
	}

	// member create

	/**
	 * @access	public
	 * @param  	
	 * @return	the member as dictionary
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.update.package_update
	 */
	public function action_member_create($data)
	{
		return $this->make_request('POST', 
			$this->actionList['member_create'], 
			$data);
	}
	
	// organization create 

	/**
	 * @access	public
	 * @param  	string organization
	 * @return	the newly created organization as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_create($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_create'], 
			$data);
	}

	// organization show

	/**
	 * @access	public
	 * @param  	string organization
	 * @return	the organization to show as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_show($id)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_show'], 
			$id);
	}

	// organization list for user

	/**
	 * @access	public
	 * @param  	user name or id
	 * @return	the organizations of the user to show as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_list_for_user($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_list_for_user'], 
			$data);
	}

	// organization list

	/**
	 * @access	public
	 * @param  	
	 * @return	the organizations of the user to show as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_list($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_list'], 
			$data);
	}

	// organization purge

	/**
	 * @access	public
	 * @param  	
	 * @return	the organizations of the user to show as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_purge($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_purge'], 
			$data);
	}


	// organization delete

	/**
	 * @access	public
	 * @param  	string  id
	 * @return	the organization
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_delete($id)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_delete'], 
			$id);
	}

	// organization update

	/**
	 * @access	public
	 * @param  	string organization
	 * @return	the updated organization as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_update($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_update'], 
			$data);
	}

	// organization revision list

	/**
	 * @access	public
	 * @param  	
	 * @return	the updated organization as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_organization_revision_list($data)
	{
		return $this->make_request('POST', 
			$this->actionList['organization_revision_list'], 
			$data);
	}

	// resource_view show

	/**
	 * @access	public
	 * @param  	string resource_view
	 * @return	the resource_view to show as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_resource_view_show($id)
	{
		return $this->make_request('POST', 
			$this->actionList['resource_view_show'], 
			$id);
	}

	// resource_view create

	/**
	 * @access	public
	 * @param  	string resource_view
	 * @return	the resource_view to be created as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_resource_view_create($resource_view)
	{
		return $this->make_request('POST', 
			$this->actionList['resource_view_create'], 
			$resource_view);
	}

	// resource_view update

	/**
	 * @access	public
	 * @param  	string resource_view
	 * @return	the resource_view to be created as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_resource_view_update($resource_view)
	{
		return $this->make_request('POST', 
			$this->actionList['resource_view_update'], 
			$resource_view);
	}

	/**
	 * @access	public
	 * @param  	string resource_id
	 * @return	the resource_views for the given resource_id as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_resource_view_list($resource_id)
	{
		return $this->make_request('POST', 
			$this->actionList['resource_view_list'], 
			$resource_id);
	}

	/**
	 * @access	public
	 * @param  	string resource_view_id
	 * @return	the resource_view_id for the given resource_view as dictionary
	 * @link	http://docs.ckan.org/en/latest/api/index.html
	 */
	public function action_resource_view_delete($resource_view_id)
	{
		return $this->make_request('POST', 
			$this->actionList['resource_view_delete'], 
			$resource_view_id);
	}


	//group_package_show

	/**
	 * @access	public
	 * @param  	string  group
	 * @return	the packages of a group dictionaries
	 * @link	http://docs.ckan.org/en/latest/apiv3.html#ckan.logic.action.
	 */
	public function action_group_package_show($id)
	{
		return $this->make_request('POST', 
			$this->actionList['group_package_show'], 
			$id);
	}

	// Public (API) methods -----------------------------------------------

	// package register resource

	/**
	 * @access	public
	 * @return	array	An array of all package IDs.
	 */
	public function get_package_register()
	{
		return $this->make_request('GET', $this->resources['package_register']);
	}

	/**
	 * @access	public
	 * @param	string	package
	 * @return	bool
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#model-api-data-formats
	 */
	public function post_package_register($id, $data)
	{
		return $this->make_request('POST', 
			$this->resources['package_register']. "/" . $id , 
			$data);
	}

	/**
	* @access  public
	* @param  string  package
	* @return  bool
	* @link  http://knowledgeforge.net/ckan/doc/ckan/api.html#model-api-data-formats
	* @since  Version 0.1.0
	* doc: https://github.com/luizsoliveira/Ckan_client-PHP/commit/patch-1
	*/
	public function post_package_update($data)
	{
		$json = json_decode($data, TRUE);
		return $this->make_request('POST', 
		$this->resources['package_register'] . "/" . $json['name'], 
		$data);
	}

	public function get_group_by_name($name)
	{
		//$e = new mb_exception("testckan: get group: ".$this->resources['group_register'] . "/" . urlencode($name));
		return $this->make_request('GET', 
		$this->resources['group_register'] . "/" . urlencode($name));
	}



	// package entity resouce

	/**
	 * @access	public
	 * @param	string	package ID
	 * @return	object	package
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#model-api-data-formats
	 */
	public function get_package_entity($package)
	{
		//$e = new mb_exception($this->resources['package_entity'] . '/' . urlencode($package));
		return $this->make_request('GET', 
			$this->resources['package_entity'] . '/' . urlencode($package));
	}

	/**
	 * @access	public
	 * @param	string	package ID
	 * @param	string	Packing
	 * @return	bool
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#model-api-data-formats
	 */
	public function put_package_entity($packageName, $data)
	{
		return $this->make_request('POST', 
			$this->resources['package_entity'] . '/' . urlencode($packageName), 
			$data);
	}

	//Following function is not allowed - it seems to be only possible to delete one package if version 3 of the api is used (action api)
	/*public function delete_package_entity($package)
	{
		return $this->make_request('DELETE', 
			$this->resources['package_entity'] . '/' . urlencode($package));
	}*/

	public function delete_package_entity($data)
	{
		//$e = new mb_exception("post: ".$data);
		return $this->make_request('POST', 
			'../action/package_delete', 
			$data);
	}

	// package utility alias

	/**
	 * CKAN package GET utility alias.
	 *
	 * @see		get_package_register(), get_package_entity()
	 * @access	public
	 */
	public function get_package($package = FALSE)
	{
		if ($package)
		{
			return $this->get_package_entity($package);
		}
		else
		{
			return $this->get_package_register();
		}
	}

	// Group register resource

	/**
	 * @access	public
	 * @return	array	An array of all group IDs.
	 */
	public function get_group_register()
	{
		return $this->make_request('GET', $this->resources['group_register']);
	}

	// Group entity resource

	/**
	 * @access	public
	 * @param	string	Group ID
	 * @return	object	Group
	 * @link	http://knowledgeforge.net/ckan/doc/ckan/api.html#model-api-data-formats
	 */
	public function get_group_entity($group)
	{
		return $this->make_request('GET', 
			$this->resources['group_entity'] . '/' . urlencode($group));
	}

	// Group utility alias

	/**
	 * CKAN group GET utility alias.
	 *
	 * @see		get_group_register(), get_group_entity()
	 * @access	public
	 */
	public function get_group($group = FALSE)
	{
		if ($group)
		{
			return $this->get_group_entity($group);
		}
		else
		{
			return $this->get_group_register();
		}
	}

	// Tag register resource

	/**
	 * @access	public
	 */
	public function get_tag_register()
	{
		return $this->make_request('GET', $this->resources['tag_register']);
	}

	// Tag entity resource

	/**
	 * @access	public
	 */
	public function get_tag_entity($tag)
	{
		return $this->make_request('GET', $this->resources['tag_entity'] . 
			'/' . urlencode($tag));
	}

	// Tag utility alias

	/**
	 * @access	public
	 */
	public function get_tag($tag = FALSE)
	{
		if ($tag)
		{
			return $this->get_tag_entity($tag);
		}
		else
		{
			return $this->get_tag_register();
		}
	}

	// Revision register resource

	/**
	 * @access	public
	 */
	public function get_revision_register()
	{
		return $this->make_request('GET', 
			$this->resources['revision_register']);
	}

	// Revision entity resource

	/**
	 * @access	public
	 */
	public function get_revision_entity($revision)
	{
		return $this->make_request('GET', 
			$this->resources['revision_entity'] . '/' . urlencode($revision));
	}

	// Revision utility alias

	/**
	 * @access	public
	 */
	public function get_revision($revision = FALSE)
	{
		if ($revision)
		{
			return $this->get_revision_entity($revision);
		}
		else
		{
			return $this->get_revision_register();
		}
	}

	// License list resource

	/**
	 * @access	public
	 */
	public function get_license_list()
	{
		return $this->make_request('GET', $this->resources['license_list']);
	}

	// License utility alias

	public function get_license()
	{
		return $this->get_license_list();
	}

	// Search API

	/**
	 * Searches CKAN packages.
	 *
	 * @access public
	 * @param	string	Keywords to search for
	 * @param	array	Optional. Search options.
	 * @return	mixed	If success, search object. On fail, false.
	 */
	public function search_package($keywords, $opts = array())
	{
		// Gots to have keywords or there's nothing to search for.
		// Also, $opts better be an array
		if (0 === strlen(trim($keywords)) || FALSE === is_array($opts))
		{
			throw new Exception('We need keywords, yo!');
		}
		$q = '';
		// Set querystring based on $opts param.
		$q .= '&order_by=' . ((isset($opts['order_by'])) 
			? urlencode($opts['order_by']) : 'rank');
		$q .= '&offset=' . ((isset($opts['offset'])) 
			? urlencode($opts['offset']) : '0');
		$q .= '&limit=' . ((isset($opts['limit'])) 
			? urlencode($opts['limit']) : '20');
		$q .= '&filter_by_openness=' . ((isset($opts['openness'])) 
			? urlencode($opts['openness']) : '0');
		$q .= '&filter_by_downloadable=' . ((isset($opts['downloadable'])) 
			? urlencode($opts['downloadable']) : '0');
		return $data = $this->make_request('GET', 
			$this->resources['package_search'] . '?q=' . 
			urlencode($keywords) . $q);
	}

	/**
	 * CKAN package search utility alias, since it's most likely ppl just
	 * want to search the packages.
	 *
	 * @see		search_package()
	 * @access	public
	 */
	public function search($keywords, $opts = array())
	{
		return $this->search_package($keywords, $opts);
	}

	// Public methods -----------------------------------------------------

	/**
	 * Helper function to ease the display of search results.
	 * Outputs directly to screen.
	 *
	 * @access	public
	 * @param	object	Result from search() or search_package()
	 * @param	array	Optional. An array of formatting options.
	 * @return	void
	 */
	public function search_display($data, $opts = array())
	{
		if ($data)
		{
			// Set vars based on $opts param.
			$search_term = (isset($opts['search_term'])) ? 
				$opts['search_term'] : '';
			$title_tag = '<' . 
				((isset($opts['title_tag'])) ? $opts['title_tag'] : 'h2') . '>';
			$title_close_tag = str_replace('<', '</', $title_tag);
			$result_list_tag = (isset($opts['result_list_tag'])) 
				? $opts['result_list_tag'] : 'ul';
			if (strlen(trim($result_list_tag)))
			{
				$result_list_close_tag = '</' . $result_list_tag . '>';
				$result_list_tag = '<' . $result_list_tag . '>';
			}
			else
			{
				$result_list_close_tag = '';
			}
			$show_notes = (isset($opts['show_notes'])) 
				? $opts['show_notes'] : FALSE;
			$format_notes = (isset($opts['format_notes'])) 
				? $opts['format_notes'] : FALSE;
			// Set search title string
			// is|are, count, ''|s, ''|search_term, .|:
			printf($title_tag . 'There %s %d result%s%s%s' . $title_close_tag, 
				(($data->count === 1) ? 'is' : 'are'), 
				$data->count, 
				(($data->count === 1) ? '' : 's'),
				(strlen(trim($search_term)) 
					? ' for &#8220;' . $search_term . '&#8221;' : ''),
				(($data->count === 0) ? '.' : ':'));
			if ($data->count > 0)
			{
				print $result_list_tag;
				foreach ($data->results as $val)
				{
					$package = $this->get_package_entity($val);
					printf('<li><a href="%s">%s</a>',
						$package->ckan_url,
						$package->title);
					if (isset($package->notes) && $package->notes && 
						$show_notes)
					{
						print ': ';
						if (TRUE === $format_notes)
						{
							print $package->notes;
						}
						elseif (FALSE === $format_notes)
						{
							print $package->notes;
						}
						else
						{
							print strip_tags($package->notes, 
								$format_notes);
						}
					}
					print '</li>';
				}
				print $result_list_close_tag;
			}
		}
	}

	// Private methods ----------------------------------------------------

	/**
	 * Make a request to the CKAN API.
	 *
	 * @access	private
	 * @param	string	HTTP method (GET, PUT, POST).
	 * @param	string	URI fragment to CKAN resource.
	 * @param	string	Optional. String in JSON-format that will be in request body.
	 * @return	mixed	If success, either an array or object. Otherwise FALSE.

	 */
	private function make_request($method, $url, $data = FALSE) {
		// Set cURL method.
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		// Set cURL URI.
$e = new mb_notice("testckan: ckan url to request: ".$this->base_url . $url." - data: ".json_encode($data));

		$curlUrl = $this->base_url . $url;
		curl_setopt($this->ch, CURLOPT_URL, $curlUrl);
//TODO - this is not secure - delete it in productive environments!
curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		// If POST or PUT, add Authorization: header and request body
		if ($method === 'POST' || $method === 'PUT') {
			// We needs a key and some data, yo!
			//don't need post data for organization_list_for_user, api_key may be enough
			/*if (strpos($url,"organization_list_for_user") !== false) {
				if ( !($this->api_key)) {
					// throw exception
					$e = new mb_exception("Missing an API key.");
				} else {
					// Add Authorization: header.
					$this->ch_headers = $this->setApiKeyInHeader($this->ch_headers, $this->api_key);
					// Add data to request body.
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
				}
			} else {*/
				if ( !($this->api_key) && !($data)) {
					// throw exception
					$e = new mb_exception("Missing either an API key or POST data or both.");
				} else {
					// Add Authorization: header.
					$this->ch_headers = $this->setApiKeyInHeader($this->ch_headers, $this->api_key);
					// Add data to request body.
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
				}
			//}
		//other method than post or put
		} else {
			// Since we can't use HTTPS,
			 // if it's in there, remove Authorization: header
			$key1 = array_search('Authorization: ' . $this->api_key, 
				$this->ch_headers);
			$key2 = array_search('X-CKAN-API-Key: ' . $this->api_key, 
				$this->ch_headers);
			if ($key1 !== FALSE) {
				unset($this->ch_headers[$key]);
			}
			if ($key2 !== FALSE) {
				unset($this->ch_headers[$key]);
			}
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, NULL);
		}
		/*foreach ($this->ch_headers as $header) {
			$e = new mb_exception("header: ".$header);
		}*/
		// Set headers.
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->ch_headers);
		// Execute request and get response headers.
		$response = curl_exec($this->ch);
		$info = curl_getinfo($this->ch);
		// Check HTTP response code
		if ($info['http_code'] !== 200)
		{
			$e = new mb_exception("CKAN API request returned ".$info['http_code'] . ': ' . $this->http_status_codes[$info['http_code']]." while requesting ".$data);
		} else {
			$e = new mb_notice("CKAN API request returned 200 - OK");
		}
		/*$e = new mb_exception("url: ".$curlUrl);
		$e = new mb_exception("send data: ".$data);
		$e = new mb_exception("response: ".$response);
		$e = new mb_exception("content_type: ".$info['content_type']);*/

		/*$this->logit("url: ".$curlUrl);
		$this->logit("send data: ".$data);
		$this->logit("response: ".$response);
		$this->logit("content_type: ".$info['content_type']);*/
		// Determine how to parse
		if (isset($info['content_type']) && $info['content_type']) {
			$content_type = str_replace('application/', '', 
				substr($info['content_type'], 0, 
				strpos($info['content_type'], ';')));
			//return $response;
			//$e = new mb_exception("content_type: ".$content_type);
			return $this->parse_response($response, $content_type);
		} else {
			$e = new mb_exception("CKAN API request returned unknown content type!");
		}
	}

	/**
	 * SET API KEY in HTTP header array
	 *
	 * @access	private
	 * @param	array  HTTP header array
	 * @param	string API key
	 * @return	mixed  If success, returns changed http header array . Otherwise FALSE.

	 */
	private function setApiKeyInHeader($headerArray, $apiKey) {
		$keyFound = false;
		//check if some key is already set
		$index = 0;
		foreach ($headerArray as $header) {
			if (strpos($header, "X-CKAN-API-Key:") !== false){
				$keyFound = $index;
			}
			$index++;
		}
		if ($keyFound !== false) {
			$headerArray[$index] = $apiKey;
		} else {
			$headerArray[] = 'X-CKAN-API-Key: ' . $apiKey;
		}
		return $headerArray;
	}

	/**
	 * Parse the response from the CKAN API.
	 *
	 * @access	private
	 * @param	string	Data returned from the CKAN API.
	 * @param	string	Format of data returned from the CKAN API.
	 * @return	mixed	If success, either an array or object. Otherwise FALSE.

	 */
	private function parse_response($data = FALSE, $format = FALSE)
	{
		if ($data)
		{
			if ($format == 'json')
			{
				return json_decode($data);
			}
			else
			{
				throw new Exception('Unable to parse this data format.');
			}
		}
		return FALSE;
	}

}
