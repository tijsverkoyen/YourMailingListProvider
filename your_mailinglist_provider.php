<?php

/**
 * Your Mailing List Provider class
 *
 * This source file can be used to communicate with Your MailingList Provider (http://ymlp.com)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by creating an issue on https://github.com/tijsverkoyen/ymlp/issues
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c), Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author			Tijs Verkoyen <php-ymlp@verkoyen.eu>
 * @version			1.0.0
 *
 * @copyright		Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license			BSD License
 */
class YourMailingListProvider
{
	// url for the bitly-api
	const API_URL = 'https://www.ymlp.com/api';

	// port for the bitly-API
	const API_PORT = 443;

	// current version
	const VERSION = '1.0.0';


	/**
	 * The API-key that will be used for authenticating
	 *
	 * @var	string
	 */
	private $apiKey;


	/**
	 * The timeout
	 *
	 * @var	int
	 */
	private $timeOut = 60;


	/**
	 * The user agent
	 *
	 * @var	string
	 */
	private $userAgent;


	/**
	 * The username that will be used for authenticating
	 *
	 * @var	string
	 */
	private $username;


// class methods
	/**
	 * Default constructor
	 *
	 * @return	void
	 * @param	string $login					The username that has to be used for authenticating.
	 * @param	string[optional] $apiKey		The API-key that has to be used for authentication.
	 */
	public function __construct($username, $apiKey)
	{
		$this->setUsername($username);
		$this->setApiKey($apiKey);
	}


	/**
	 * Make the call
	 *
	 * @return	mixed
	 * @param	string $url						The URL to call.
	 * @param	array[optional] $parameters		The parameters to send.
	 * @param	string[optional] $method		The method to use, possible values are: GET, POST.
	 * @param	bool[optional] $expectJSON		Do we expect JSON?
	 */
	private function doCall($url, array $parameters = null, $method = 'GET', $expectJSON = true)
	{
		// redefine
		$url = (string) $url;
		$method = (string) $method;

		// validate
		if(!in_array($method, array('GET', 'POST'))) throw new YourMailingListProviderException('Invalid method.');

		// prepend
		$url = self::API_URL .'/'. $url;

		// set options
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_PORT] = self::API_PORT;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		if(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = false;

		// add authentication stuff
		$parameters['Key'] = $this->getApiKey();
		$parameters['Username'] = $this->getUsername();

		// add output
		$parameters['Output'] = 'JSON';

		// POST
		if($method == 'POST')
		{
			// reset
			$options[CURLOPT_POST] = true;

			// ant parameters?
			if(!empty($parameters)) $options[CURLOPT_POSTFIELDS] = $parameters;
		}

		// GET
		else
		{
			// reset
			$options[CURLOPT_POST] = false;

			// any parameters?
			if(!empty($parameters))
			{
				if(substr_count($url, '?') > 0) $options[CURLOPT_URL] .= '&'. http_build_query($parameters);
				else $options[CURLOPT_URL] .= '?'. http_build_query($parameters);
			}
		}

		// init
		$curl = curl_init();

		// set options
		curl_setopt_array($curl, $options);

		// execute
		$response = curl_exec($curl);
		$headers = curl_getinfo($curl);

		// fetch errors
		$errorNumber = curl_errno($curl);
		$errorMessage = curl_error($curl);

		// close
		curl_close($curl);

		// error?
		if($errorNumber != '') throw new YourMailingListProviderException($errorMessage, $errorNumber);

		// we don't expect JSON
		if(!$expectJSON) return $response;

		// we expect JSON so decode it
		$json = @json_decode($response, true);

		// validate json
		if($json === false) throw new YourMailingListProviderException('Invalid JSON-response');

		// is error?
		if(isset($json['Code']) && $json['Code'] != 0) throw new YourMailingListProviderException((string) $json['Output'], (int) $json['Code']);

		// return
		return $json['Output'];
	}


	/**
	 * Get the APIkey
	 *
	 * @return	mixed
	 */
	private function getApiKey()
	{
		return $this->apiKey;
	}


	/**
	 * Get the timeout that will be used
	 *
	 * @return	int
	 */
	public function getTimeOut()
	{
		return (int) $this->timeOut;
	}


	/**
	 * Get the useragent that will be used. Our version will be prepended to yours.
	 * It will look like: "PHP GitHub/<version> <your-user-agent>"
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP GitHub/'. self::VERSION .' '. $this->userAgent;
	}


	/**
	 * Get the username
	 *
	 * @return	string
	 */
	private function getUsername()
	{
		return (string) $this->username;
	}


	/**
	 * Set the API-key that has to be used
	 *
	 * @return	void
	 * @param	string $apiKey
	 */
	private function setApiKey($apiKey)
	{
		$this->apiKey = (string) $apiKey;
	}


	/**
	 * Set the password that has to be used
	 *
	 * @return	void
	 * @param	string $password
	 */
	private function setPassword($password)
	{
		$this->password = (string) $password;
	}


	/**
	 * Set the timeout
	 * After this time the request will stop. You should handle any errors triggered by this.
	 *
	 * @return	void
	 * @param	int $seconds	The timeout in seconds
	 */
	public function setTimeOut($seconds)
	{
		$this->timeOut = (int) $seconds;
	}


	/**
	 * Set the user-agent for you application
	 * It will be appended to ours, the result will look like: "PHP GitHub/<version> <your-user-agent>"
	 *
	 * @return	void
	 * @param	string $userAgent	Your user-agent, it should look like <app-name>/<app-version>
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}


	/**
	 * Set the username that has to be used
	 *
	 * @return	void
	 * @param	string $username
	 */
	private function setUsername($username)
	{
		$this->username = (string) $username;
	}


// general
	/**
	 * Ping is the simplest command, doesn't serve any useful purpose but is a great command to understand this API feature and to test your API implementation.
	 * When you call this command, it will return "Hello!"
	 *
	 * @return	string
	 */
	public function ping()
	{
		// set url
		$url = 'Ping';

		// make the call
		return $this->doCall($url);
	}


// contacts
	/**
	 * Adds a new contact to one or more groups in your database.
	 *
	 * @return	bool
	 * @param	string $email
	 * @param	array $groups									An array with the ids of the groups.
	 * @param	array[optional] $fields							An key-value-pair array, where the key is the id of the field.
	 * @param	bool[optional] $overruleUnsubscribedBounced		If true the e-mailadress will be added even if this persion previously unsubscribed or if the email address previously was removed by bounce back handling.
	 */
	public function contactsAdd($email, array $groups, array $fields = null, $overruleUnsubscribedBounced = false)
	{
		// redefine
		$email = (string) $email;
		$overruleUnsubscribedBounced = (bool) $overruleUnsubscribedBounced;

		// set url
		$url = 'Contacts.Add';

		// build parameters
		$parameters['Email'] = $email;

		// any fields?
		if(!empty($fields))
		{
			// loop fields and add them
			foreach($fields as $id => $value) $parameters['Field'. $id] = $value;
		}

		// group
		$parameters['GroupID'] = implode(',', $groups);

		// overrule?
		if($overruleUnsubscribedBounced) $parameters['OverruleUnsubscribedBounced'] = 1;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == $email .' has been added');
	}


	/**
	 * Removes a given email address from one or more groups.
	 *
	 * @return	bool
	 * @param	string $email
	 * @param	array $groups	An array with the ids of the groups.
	 */
	public function contactsDelete($email, array $groups)
	{
		// redefine
		$email = (string) $email;

		// set url
		$url = 'Contacts.Delete';

		// build parameters
		$parameters['Email'] = $email;
		$parameters['GroupID'] = implode(',', $groups);

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == $email .' has been removed');
	}


	/**
	 * Unsubscribes a given email address.
	 *
	 * @return	bool
	 * @param	string $email
	 */
	public function contactsUnsubscribe($email)
	{
		// redefine
		$email = (string) $email;

		// set url
		$url = 'Contacts.Unsubscribe';

		// build parameters
		$parameters['Email'] = $email;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == $email .' has been unsubscribed');
	}


	/**
	 * Retrieves all available information regarding a contact.
	 *
	 * @return	array
	 * @param	string $email
	 */
	public function contactsGetContact($email)
	{
		// redefine
		$email = (string) $email;

		// set url
		$url = 'Contacts.GetContact';

		// build parameters
		$parameters['Email'] = $email;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of contacts in a given list of groups.
	 *
	 * @return	array
	 * @param	array $groups					An array with the ids of the groups.
	 * @param	array $fields					An array with the ids of the fields.
	 * @param	int[optional] $startDate		Only show contacts that were added after this date.
	 * @param	int[optional] $stopDate			Only show contacts that were added before this date.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	number of contacts per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function contactsGetList(array $groups, array $fields, $startDate = null, $stopDate = null, $page = null, $numberPerPage = null, $sorting = null)
	{
		// set url
		$url = 'Contacts.GetList';

		// build parameters
		$parameters['GroupID'] = implode(',', $groups);
		$parameters['FieldID'] = implode(',', $fields);
		if($startDate !== null) $parameters['StartDate'] = date('Y-m-d', (int) $startDate);
		if($stopDate !== null) $parameters['StopDate'] = date('Y-m-d', (int) $stopDate);
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of unsubscribed contacts in your account.
	 *
	 * @return	array
	 * @param	array $fields					An array with the ids of the fields.
	 * @param	int[optional] $startDate		Only show contacts that were added after this date.
	 * @param	int[optional] $stopDate			Only show contacts that were added before this date.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	number of contacts per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function contactsGetUnsubscribed(array $fields, $startDate = null, $stopDate = null, $page = null, $numberPerPage = null, $sorting = null)
	{
		// set url
		$url = 'Contacts.GetUnsubscribed';

		// build parameters
		$parameters['FieldID'] = implode(',', $fields);
		if($startDate !== null) $parameters['StartDate'] = date('Y-m-d', (int) $startDate);
		if($stopDate !== null) $parameters['StopDate'] = date('Y-m-d', (int) $stopDate);
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of manually removed contacts in your account.
	 *
	 * @return	array
	 * @param	array $fields					An array with the ids of the fields.
	 * @param	int[optional] $startDate		Only show contacts that were added after this date.
	 * @param	int[optional] $stopDate			Only show contacts that were added before this date.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	number of contacts per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function contactsGetDeleted(array $fields, $startDate = null, $stopDate = null, $page = null, $numberPerPage = null, $sorting = null)
	{
		// set url
		$url = 'Contacts.GetDeleted';

		// build parameters
		$parameters['FieldID'] = implode(',', $fields);
		if($startDate !== null) $parameters['StartDate'] = date('Y-m-d', (int) $startDate);
		if($stopDate !== null) $parameters['StopDate'] = date('Y-m-d', (int) $stopDate);
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of contacts removed by bounce back handling in your account.
	 *
	 * @return	array
	 * @param	array $fields					An array with the ids of the fields.
	 * @param	int[optional] $startDate		Only show contacts that were added after this date.
	 * @param	int[optional] $stopDate			Only show contacts that were added before this date.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	number of contacts per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function contactsGetBounced(array $fields, $startDate = null, $stopDate = null, $page = null, $numberPerPage = null, $sorting = null)
	{
		// set url
		$url = 'Contacts.GetBounced';

		// build parameters
		$parameters['FieldID'] = implode(',', $fields);
		if($startDate !== null) $parameters['StartDate'] = date('Y-m-d', (int) $startDate);
		if($stopDate !== null) $parameters['StopDate'] = date('Y-m-d', (int) $stopDate);
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Lists the groups in your account, along with their group IDs and the number of contacts in each group.
	 *
	 * @return	array
	 */
	public function GroupsGetList()
	{
		// set url
		$url = 'Groups.GetList';

		// make the call
		return $this->doCall($url);
	}


	/**
	 * Creates a new group.
	 *
	 * @return	string
	 * @param	string $name	Label to use for the new group
	 */
	public function GroupsAdd($name)
	{
		// redefine
		$name = (string) $name;

		// set url
		$url = 'Groups.Add';

		// build parameters
		$parameters['GroupName'] = $name;

		// make the call
		return str_replace('ID: ', '', $this->doCall($url, $parameters, 'POST'));
	}


	/**
	 * Removes a group based on a given group ID.
	 *
	 * @return	bool
	 * @param	string $id		ID of the group.
	 */
	public function GroupsDelete($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Groups.Delete';

		// build parameters
		$parameters['GroupID'] = $id;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Removed ID: '. $id);
	}


	/**
	 * Update the properties of a group.
	 *
	 * @return	bool
	 * @param	string $id		ID of teh group.
	 * @param	string $name	New label to use for this group.
	 */
	public function GroupsUpdate($id, $name)
	{
		// redefine
		$id = (string) $id;
		$name = (string) $name;

		// set url
		$url = 'Groups.Update';

		// build parameters
		$parameters['GroupID'] = $id;
		$parameters['GroupName'] = $name;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Updated ID: '. $id);
	}


	/**
	 * Remove all contacts in a group.
	 *
	 * @return	string
	 * @param	string $id
	 */
	public function GroupsEmpty($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Groups.Empty';

		// build parameters
		$parameters['GroupID'] = $id;

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}


	/**
	 * Lists the fields in your account along with the field ID, the alias, the default value and the "Correct Uppercase" value for each field.
	 *
	 * @return	array
	 */
	public function FieldsGetList()
	{
		// set url
		$url = 'Fields.GetList';

		// make the call
		return $this->doCall($url);
	}


	/**
	 * Creates a new field.
	 *
	 * @return	string
	 * @param	string $name						Label to use for the new field.
	 * @param	string[optional] $alias				Alias for the new field, defaults to the field name.
	 * @param	mixed[optional] $default
	 * @param	bool[optional] $correctUppercase
	 */
	public function FieldsAdd($name, $alias = null, $default = null, $correctUppercase = false)
	{
		// redefine
		$name = (string) $name;
		$correctUppercase = (bool) $correctUppercase;

		// set url
		$url = 'Fields.Add';

		// build parameters
		$parameters['FieldName'] = $name;
		if($alias !== null) $parameters['Alias'] = (string) $alias;
		if($default !== null) $parameters['DefaultValue'] = $default;
		if($correctUppercase) $parameters['CorreectUppercase'] = 1;


		// make the call
		return str_replace('ID: ', '', $this->doCall($url, $parameters, 'POST'));
	}


	/**
	 * Removes a field based on a given field ID.
	 *
	 * @return	bool
	 * @param	string $id
	 */
	public function FieldsDelete($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Fields.Delete';

		// build parameters
		$parameters['FieldID'] = $id;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Removed ID: '. $id);
	}


	/**
	 * Update the properties of a field.
	 *
	 * @return	bool
	 * @param	string $id
	 * @param	string[optional] $name
	 * @param	string[optional] $alias
	 * @param	mixed[optional] $default
	 * @param	bool[optional] $correctUppercase
	 */
	public function FieldsUpdate($id, $name = null, $alias = null, $default = null, $correctUppercase = null)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Fields.Update';

		// build parameters
		$parameters['FieldID'] = $id;
		if($name !== null) $parameters['FieldName'] = (string) $name;
		if($alias !== null) $parameters['Alias'] = (string) $alias;
		if($default !== null) $parameters['Default'] = $default;
		if($correctUppercase !== null) $parameters['CorrectUppercase'] = ((bool) $correctUppercase) ? '1' : '0';

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Updated ID: '. $id);
	}


	/**
	 * Lists the filters in your account along with the filter ID, the filter name, the criterion description and the field-operand-value combination for each filter.
	 *
	 * @return	array
	 * @param	bool[optional] $overruleDeleted		Whether or not to include deleted filters in the output.
	 */
	public function FiltersGetList($overruleDeleted = false)
	{
		// redefine
		$overruleDeleted = (bool) $overruleDeleted;

		// set url
		$url = 'Filters.GetList';

		// build parameters
		if($overruleDeleted) $parameters['OverruleDeleted'] = '1';

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Creates a new filter.
	 *
	 * @return	string
	 * @param	string $name
	 * @param	string $field
	 * @param	string $operand
	 * @param	string $value
	 */
	public function FiltersAdd($name, $field, $operand, $value)
	{
		// redefine
		$name = (string) $name;
		$field = (string) $field;
		$operand = (string) $operand;
		$value = (string) $value;

		// set url
		$url = 'Filters.Add';

		// build parameters
		$parameters['FilterName'] = $name;
		$parameters['Field'] = $field;
		$parameters['Operand'] = $operand;
		$parameters['Value'] = $value;

		// make the call
		return str_replace('ID: ', '', $this->doCall($url, $parameters, 'POST'));
	}


	/**
	 * Removes a filter based on a given filter ID.
	 *
	 * @return	bool
	 * @param	string $id	ID of the filter.
	 */
	public function FiltersDelete($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Filters.Delete';

		// build parameters
		$parameters['FilterID'] = $id;

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}


// send messages
	/**
	 * Returns the list of available sender addresses in the account.
	 *
	 * @return	array
	 */
	public function newsletterGetFroms()
	{
		// set url
		$url = 'Newsletter.GetFroms';

		// make the call
		return $this->doCall($url);
	}


	/**
	 * Creates a new sender address.
	 *
	 * @return	string
	 * @param	string $email
	 * @param	string $name		Name/description for the new sender address.
	 */
	public function newsletterAddFrom($email, $name)
	{
		// redefine
		$email = (string) $email;
		$name = (string) $name;

		// set url
		$url = 'Newsletter.AddFrom';

		// build parameters
		$parameters['FromEmail'] = $email;
		$parameters['FromName'] = $name;

		// make the call
		return str_replace('ID: ', '', $this->doCall($url, $parameters, 'POST'));
	}


	/**
	 * Removes a sender address based on a given From ID.
	 *
	 * @return	bool
	 * @param	string $id
	 */
	public function newsletterDeleteFrom($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Newsletter.DeleteFrom';

		// build parameters
		$parameters['FromID'] = $id;

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Removed ID: '. $id);
	}


	/**
	 * Queues a message for delivery.
	 *
	 * @return	bool
	 * @param	string $subject
	 * @param	string[optional] $html
	 * @param	string[optional] $text
	 * @param	int[optional] $deliveryTime
	 * @param	string $fromID
	 * @param	bool[optional] $trackOpens
	 * @param	bool[optional] $trackClicks
	 * @param	bool[optional] $testMessage
	 * @param	array $groups
	 * @param	array[optional] $filters
	 * @param	bool[optional] $combineFilters
	 * @throws YourMailingListProviderException
	 */
	public function newsletterSend($subject, $html = null, $text = null, $deliveryTime = null, $fromID, $trackOpens = null, $trackClicks = null, $testMessage = null, array $groups, array $filters = null, $combineFilters = null)
	{
		// redefine
		$subject = (string) $subject;

		// set url
		$url = 'Newsletter.Send';

		// build parameters
		$parameters['Subject'] = $subject;
		if($html !== null) $parameters['HTML'] = (string) $html;
		if($text !== null) $parameters['Text'] = (string) $text;
		if($deliveryTime !== null) $parameters['DeliveryTime'] = date('Y-m-d H:m', (int) $deliveryTime);
		$parameters['FromID'] = (string) $fromID;
		if($trackOpens !== null && $trackOpens) $parameters['TrackOpens'] = '1';
		if($trackClicks !== null && $trackClicks) $parameters['TrackClicks'] = '1';
		if($testMessage !== null && $testMessage) $parameters['TestMessage'] = '1';
		$parameters['GroupID'] = implode(',', $groups);
		if($filters !== null) $parameters['FilterID'] = implode(',', $filters);
		if($combineFilters !== null && $combineFilters) $parameters['CombineFilters'] = '1';

		// make the call
		return ($this->doCall($url, $parameters, 'POST') == 'Message queued for delivery');
	}


// read data
	/**
	 * Returns a list of newsletters in the archives of your account.
	 *
	 * @return	array
	 * @param	int[optional] $page					ID of the result page to show.
	 * @param	int[optional] $numberPerPage		Number of newsletters per result page.
	 * @param	int[optional] $startDate			Only return newsletters that were sent after this date.
	 * @param	int[optional] $stopDate				Only return newsletters that were sent before this date.
	 * @param	string[optional] $sorting			Sorting order of the returned newsletters, either Ascending or Descending
	 * @param	bool[optional] $showTestMessages	Whether or not to include test messages in the output.
	 */
	public function archiveGetList($page = null, $numberPerPage = null, $startDate = null, $stopDate = null, $sorting = null, $showTestMessages = null)
	{
		// set url
		$url = 'Archive.GetList';

		// build parameters
		if($page !== null) $parameter['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($startDate !== null) $parameters['StartDate'] = date('Y-m-d', (int) $startDate);
		if($stopDate !== null) $parameters['StopDate'] = date('Y-m-d', (int) $stopDate);
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;
		if($showTestMessages !== null && $showTestMessages) $parameters['ShowtestMessages'] = '1';

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns all available information regarding a newsletter, except for its content.
	 *
	 * @return	array
	 * @param	string $id	ID of the Newsletter.
	 */
	public function archiveGetSummary($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Archive.GetSummary';

		// build parameters
		$parameters['NewsletterID'] = $id;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of email addresses a newsletter was sent to.
	 *
	 * @return	array
	 * @param	string $id						ID of the Newsletter
	 * @param	int[optional] $page				ID of the result page to show
	 * @param	int[optional] $numberPerPage	Number of email addresses per result page
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending
	 */
	public function archiveGetRecipients($id, $page = null, $numberPerPage, $sorting = null)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Archive.GetRecipients';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of email addresses a newsletter was successfully delivered to.
	 *
	 * @return	array
	 * @param	string $id						ID of the Newsletter.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	Number of email addresses per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetDelivered($id, $page = null, $numberPerPage, $sorting = null)
	{
		// set url
		$url = 'Archive.GetDelivered';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the bouncebacks for a newsletter.
	 *
	 * @param	string $id							ID of the Newsletter.
	 * @param	bool[optional] $showHardBounces		Whether to include email addresses that returned a permanent error or "hard" bounceback.
	 * @param	bool[optional] $showSoftBounces		Whether to include email addresses that returned a temporary error or "soft" bounceback.
	 * @param	int[optional] $page					ID of the result page to show.
	 * @param	int[optional] $numberPerPage		Number of email addresses per result page.
	 * @param	string[optional] $sorting			Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetBounces($id, $showHardBounces = null, $showSoftBounces = null, $page = null, $numberPerPage, $sorting = null)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Archive.GetBounces';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($showHardBounces !== null && $showHardBounces) $parameters['ShowHardBounces'] = '1';
		if($showSoftBounces !== null && $showSoftBounces) $parameters['ShowSoftBounces'] = '1';
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of email addresses that opened a newsletter.
	 *
	 * @return	array
	 * @param	string $id							ID of the Newsletter.
	 * @param	bool[optional] $showUniqueOpens		Whether or not to list only the first open from an email address, if that email address opened multiple times.
	 * @param	int[optional] $page					ID of the result page to show.
	 * @param	int[optional] $numberPerPage		Number of email addresses per result page.
	 * @param	string[optional] $sorting			Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetOpens($id, $showUniqueOpens = null, $page = null, $numberPerPage, $sorting = null)
	{
		// set url
		$url = 'Archive.GetOpens';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($showUniqueOpens !== null && $showUniqueOpens) $parameters['UniqueOpens'] = '1';
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of email addresses that opened a newsletter.
	 *
	 * @return	array
	 * @param	string $id							ID of the Newsletter.
	 * @param	int[optional] $page					ID of the result page to show.
	 * @param	int[optional] $numberPerPage		Number of email addresses per result page.
	 * @param	string[optional] $sorting			Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetUnopened($id, $page = null, $numberPerPage, $sorting = null)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Archive.GetUnopened';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of tracked links for a newsletter that was sent with click tracking.
	 *
	 * @return	array
	 * @param	string $id	ID of the Newsletter.
	 */
	public function archiveGetTrackedLinks($id)
	{
		// redefine
		$id = (string) $id;

		// set url
		$url = 'Archive.GetTrackedLinks';

		// build parameters
		$parameters['NewsletterID'] = $id;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of clicks for a newsletter that was sent with click tracking, either for all links in the newsletter or for a given link.
	 *
	 * @return	array
	 * @param	string $id							ID of the Newsletter.
	 * @param	string[optional] $linkId			ID of a link if you want to limit the results to the clicks on a particular link
	 * @param	bool[optional] $showUniqueClicks	Whether or not to list only the first click from an email address, if that email address clicked multiple times.
	 * @param	int[optional] $page					ID of the result page to show.
	 * @param	int[optional] $numberPerPage		Number of email addresses per result page.
	 * @param	string[optional] $sorting			Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetClicks($id, $linkId = null, $showUniqueClicks = null, $page = null, $numberPerPage, $sorting = null)
	{
		// set url
		$url = 'Archive.GetClicks';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($linkId !== null) $parameters['LinkID'] = (string) $linkId;
		if($showUniqueClicks !== null && $showUniqueClicks) $parameters['UniqueClicks'] = '1';
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}


	/**
	 * Returns the list of forwards for a newsletter.
	 *
	 * @return	array
	 * @param	string $id						ID of the Newsletter.
	 * @param	int[optional] $page				ID of the result page to show.
	 * @param	int[optional] $numberPerPage	Number of email addresses per result page.
	 * @param	string[optional] $sorting		Sorting order of the returned email addresses, either Ascending or Descending.
	 */
	public function archiveGetForwards($id, $page = null, $numberPerPage, $sorting = null)
	{
		// set url
		$url = 'Archive.GetForwards';

		// build parameters
		$parameters['NewsletterID'] = $id;
		if($page !== null) $parameters['Page'] = (int) $page;
		if($numberPerPage !== null) $parameters['NumberPerPage'] = (int) $numberPerPage;
		if($sorting !== null) $parameters['Sorting'] = (string) $sorting;

		// make the call
		return $this->doCall($url, $parameters);
	}
}


/**
 * YourMailingListProvider Exception class
 *
 * @author	Tijs Verkoyen <php-ymlp@verkoyen.eu>
 */
class YourMailingListProviderException extends Exception
{
}

?>