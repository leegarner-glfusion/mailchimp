<?php
/**
 * Super-simple, minimum abstraction MailChimp API v3 wrapper.
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.2
 */
//namespace DrewM\MailChimp;
namespace Mailchimp;


/**
 * MailChimp API v3: http://developer.mailchimp.com.
 * This wrapper: https://github.com/drewm/mailchimp-api.
 */
class API
{
    /** Mailchimp API key.
     * @var string */
    private $api_key;

    /** Mailchimp endpoint.
     * @var string */
    private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';

    /** Request timeout in seconds.
     * @const integer */
    const TIMEOUT = 10;

    /**
     * SSL Verification.
     * Read before disabling:
     * http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
     * @var boolean
     */
    public $verify_ssl = true;

    /** Indicator of the request status.
     * @var boolean */
    private $request_successful = false;

    /** Last error encountered.
     * @var string */
    private $last_error         = '';

    /** Last response received.
     * @var array */
    private $last_response      = array();

    /** Details of last request.
     * @var array */
    private $last_request       = array();

    /**
     * Create a new instance.
     *
     * @param   string $api_key Your MailChimp API key
     * @param   string $api_endpoint Optional custom API endpoint
     * @throws  \Exception
     */
    public function __construct($api_key, $api_endpoint = null)
    {
        $this->api_key = $api_key;
        
        if ($api_endpoint === null) {
            if (strpos($this->api_key, '-') === false) {
                throw new \Exception("Invalid MailChimp API key `{$api_key}` supplied.");
            }
            list(, $data_center) = explode('-', $this->api_key);
            $this->api_endpoint  = str_replace('<dc>', $data_center, $this->api_endpoint);
        } else {
            $this->api_endpoint  = $api_endpoint;
        }

        $this->last_response = array('headers' => null, 'body' => null);
    }


    /**
     * Get an instance of the API class.
     *
     * @author  Lee Garner <lee@leegarner.com>
     * @return  object  Mailchimp API object
     */
    public static function getInstance()
    {
        global $_CONF_MLCH;
        static $mc = NULL;
        if ($mc === NULL) {
            $mc = new self($_CONF_MLCH['api_key']);
        }
        return $mc;
    }


    /**
     * Create a new instance of a Batch request. Optionally with the ID of an existing batch.
     *
     * @param   string  $batch_id Optional ID of an existing batch, if you need to check its status for example.
     * @return  Batch            New Batch object.
     */
    public function new_batch($batch_id = null)
    {
        require_once dirname(__FILE__) . '/Batch.class.php';
        return new Batch($this, $batch_id);
    }

    /**
     * Get the API Endpoint URL (private variable).
     *
     * @return  string  The url to the API endpoint
     */
    public function getApiEndpoint()
    {
        return $this->api_endpoint;
    }


    /**
     * Convert an email address into a 'subscriber hash' for identifying the subscriber in a method URL.
     * @param   string $email The subscriber's email address
     * @return  string          Hashed version of the input
     */
    public function subscriberHash($email)
    {
        return md5(strtolower($email));
    }


    /**
     * Was the last request successful?
     *
     * @return  boolean     True for success, false for failure
     */
    public function success()
    {
        return $this->request_successful;
    }


    /**
     * Get the last error returned by either the network transport, or by the API.
     * If something didn't work, this should contain the string describing the problem.
     *
     * @return  string|false  Describing the error
     */
    public function getLastError()
    {
        return $this->last_error ?: false;
    }


    /**
     * Get an array containing the HTTP headers and the body of the API response.
     *
     * @return  array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API request.
     *
     * @return  array  Assoc array
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    /**
     * Make an HTTP DELETE request - for deleting data.
     *
     * @param   string  $method     URL of the API request method
     * @param   array   $args       Assoc array of arguments (if any)
     * @param   integer $timeout    Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function delete($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }


    /**
     * Make an HTTP GET request - for retrieving data.
     *
     * @param   string  $method     URL of the API request method
     * @param   array   $args       Assoc array of arguments (usually your data)
     * @param   integer $timeout    Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function get($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }


    /**
     * Make an HTTP PATCH request - for performing partial updates.
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function patch($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    /**
     * Make an HTTP POST request - for creating and updating items.
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PUT request - for creating new items.
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function put($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }


    /**
     * Get a list of members subscribed to a given list.
     *
     * @param   string  $list_id    Mailing List ID
     * @param   string  $status     Member status, default=subscribed
     * @param   mixed   $since      Starting date for subscriptions
     * @param   integer $offset     Offset to retrieve, in case of large lists
     * @param   integer $count      Maximum number of members to retrieve.
     * @return  array       Array of data
     */
    public function listMembers($list_id, $status='subscribed', $since=NULL, $offset=0, $count=100)
    {
        return $this->get("lists/$list_id/members");
    }


    /**
     * Get an array of mailing lists visible to this API key.
     *
     * @param   array   $fields     Fields to retrieve
     * @param   integer $offset     First record offset, for larget datasets
     * @param   integer $count      Number of items to return
     * @return  array       Array of list data
     */
    public function lists($fields=array(), $offset=0, $count=25)
    {
        $params = array(
            'fields' => $fields,
            'offset' => $offset,
            'count' => $count,
        );
        return $this->get('lists', $params);
    }


    /**
     * Get an array of lists for which a specific email address is subscribed.
     *
     * @param   string  $email      Email address
     * @param   array   $fields     Fields to retrieve
     * @param   integer $offset     First record offset, for larget datasets
     * @param   integer $count      Number of items to return
     * @return  array       Array of list data
     */
    public function listsForEmail($email, $fields=array(), $offset=0, $count=25)
    {
        if (empty($fields)) {
            $fields = array('lists');
        };
        $params = array(
            'fields' => $fields,
            'offset' => $offset,
            'count' => $count,
            'email' => $email,
        );
        return $this->get('lists', $params);
    }


    /**
     * Unsubscribe an email address from one or more lists.
     *
     * @param   string  $email      Email address
     * @param   array   $lists      Array of list IDs
     * @return  boolean     True on success, False on error
     */
    public function unsubscribe($email, $lists=array())
    {
        $status = false;
        if (!is_array($lists)) $lists = array($lists);
        if (empty($lists)) return true;     // no lists specified, consider success
        $email = $this->subscriberhash($email);
        foreach ($lists as $list_id) {
            $status = $this->delete("/lists/$list_id/members/$email");
        }
        return $status;
    }


    /**
     * Subscribe an email address to one or more lists.
     *
     * @param   string  $email      Email address
     * @param   array   $args       Array of additional args to supply
     * @param   array   $lists      Array of list IDs
     * @return  boolean     True on success, False on error
     */
    public function subscribe($email, $args=array(), $lists=array())
    {
        if (!is_array($lists)) $lists = array($lists);
        if (empty($lists)) return true;
        if (!isset($args['email_address'])) $args['email_address'] = $email;
        foreach ($lists as $list_id) {
            $status = $this->post("/lists/$list_id/members/", $args);
        }
        return $status;
    }


    /**
     * Get information about a specific member by email address.
     *
     * @param   string  $email      Email address
     * @return  array       Array of member information
     */
    public function memberInfo($email)
    {
        $params = array(
            'query' => $email,
        );
        return $this->get('/search-members', $params);
    }


    /**
     * Update the member information for a specific email and list.
     *
     * @param   string  $email      Email address
     * @param   string  $list_id    Mailing List ID
     * @param   array   $params     Array of parameters to update
     * @return      True on success, False on failure
     */
    public function updateMember($email, $list_id, $params=array())
    {
        $hash = $this->subscriberHash($email);
        return $this->patch("/lists/$list_id/members/$hash", $params);
    }


    /**
     * Call an API method.
     * Every request needs the API key, so that is added automatically -- you don't need to pass it in.
     *
     * @param  string   $method The API method to call, e.g. 'lists/list'
     * @param  array    $args   An array of arguments to pass to the method. Will be json-encoded for you.
     * @return array            Associative array of json decoded API response.
     */
    public function call($method, $args=array())
    {
        return $this->makeRequest('get', $method, $args);
    }


    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param   string  $http_verb  The HTTP verb to use: get, post, put, patch, delete
     * @param   string  $method     The API method to be called
     * @param   array   $args       Assoc array of parameters to be passed
     * @param   integer $timeout    Request timeout
     * @return  array|false Assoc array of decoded result
     * @throws  \Exception
     */
    private function makeRequest($http_verb, $method, $args = array(), $timeout = self::TIMEOUT)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $url = $this->api_endpoint . '/' . $method;

        $response = $this->prepareStateForRequest($http_verb, $method, $url, $timeout);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: apikey ' . $this->api_key
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/MailChimp-API/3.0 (github.com/drewm/mailchimp-api)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
                break;

            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;

            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
                break;

            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }
        
        $responseContent     = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response            = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse   = $this->formatResponse($response);

        curl_close($ch);

        $this->determineSuccess($response, $formattedResponse, $timeout);

        return $formattedResponse;
    }

    /**
     * Prepare local variables to receive data from the HTTP request.
     *
     * @param   string  $http_verb  HTTP verb (GET, POST, etc.)
     * @param   string  $method     API method being called
     * @param   string  $url        API Endpoint url, including $method
     * @param   integer $timeout    Request timeout in seconds
     */
    private function prepareStateForRequest($http_verb, $method, $url, $timeout)
    {
        $this->last_error = '';
        
        $this->request_successful = false;

        $this->last_response = array(
            'headers'     => null, // array of details from curl_getinfo()
            'httpHeaders' => null, // array of HTTP headers
            'body'        => null // content of the response
        );

        $this->last_request = array(
            'method'  => $http_verb,
            'path'    => $method,
            'url'     => $url,
            'body'    => '',
            'timeout' => $timeout,
        );

        return $this->last_response;
    }

    /**
     * Get the HTTP headers as an array of header-name => header-value pairs.
     * 
     * The "Link" header is parsed into an associative array based on the
     * rel names it contains. The original value is available under
     * the "_raw" key.
     * 
     * @param   string  $headersAsString    Header string
     * @return  array       Headers as an associative array
     */
    private function getHeadersAsArray($headersAsString)
    {
        $headers = array();
        
        foreach (explode("\r\n", $headersAsString) as $i => $line) {
            if ($i === 0) { // HTTP code
                continue;
            }
            
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            list($key, $value) = explode(': ', $line);
            
            if ($key == 'Link') {
                $value = array_merge(
                    array('_raw' => $value),
                    $this->getLinkHeaderAsArray($value)
                );
            }
            
            $headers[$key] = $value;
        }

        return $headers;
    }


    /**
     * Extract all rel => URL pairs from the provided Link header value.
     * 
     * Mailchimp only implements the URI reference and relation type from
     * RFC 5988, so the value of the header is something like this:
     * 
     * 'https://us13.api.mailchimp.com/schema/3.0/Lists/Instance.json; rel="describedBy", <https://us13.admin.mailchimp.com/lists/members/?id=XXXX>; rel="dashboard"'
     * 
     * @param   string  $linkHeaderAsString     Rel-type header as a string
     * @return  array       Associative array of the Rel header
     */
    private function getLinkHeaderAsArray($linkHeaderAsString)
    {
        $urls = array();
        
        if (preg_match_all('/<(.*?)>\s*;\s*rel="(.*?)"\s*/', $linkHeaderAsString, $matches)) {
            foreach ($matches[2] as $i => $relName) {
                $urls[$relName] = $matches[1][$i];
            }
        }
        
        return $urls;
    }


    /**
     * Encode the data and attach it to the request.
     *
     * @param   resource    $ch     cURL session handle, used by reference
     * @param   array       $data   Assoc array of data to attach
     */
    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }


    /**
     * Decode the response and format any error messages for debugging.
     *
     * @param   array   $response   The response from the curl request
     * @return  array|false    The JSON decoded into an array
     */
    private function formatResponse($response)
    {
        $this->last_response = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }

        return false;
    }

    /**
     * Do post-request formatting and setting state from the response.
     *
     * @param   array   $response       The response from the curl request
     * @param   string  $responseContent The body of the response from the curl request
     * @param   reference   $ch         Curl handler
     * @return  array    The modified response
     */
    private function setResponseState($response, $responseContent, $ch)
    {
        if ($responseContent === false) {
            $this->last_error = curl_error($ch);
        } else {
        
            $headerSize = $response['headers']['header_size'];
            
            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body'] = substr($responseContent, $headerSize);

            if (isset($response['headers']['request_header'])) {
                $this->last_request['headers'] = $response['headers']['request_header'];
            }
        }

        return $response;
    }

    /**
     * Check if the response was successful or a failure. If it failed, store the error.
     *
     * @param   array   $response   The response from the curl request
     * @param   array|false $formattedResponse  The response body payload from the curl request
     * @param   integer $timeout    The timeout supplied to the curl request.
     * @return  boolean     If the request was successful
     */
    private function determineSuccess($response, $formattedResponse, $timeout)
    {
        $status = $this->findHTTPStatus($response, $formattedResponse);

        if ($status >= 200 && $status <= 299) {
            $this->request_successful = true;
            return true;
        }

        if (isset($formattedResponse['detail'])) {
            $this->last_error = sprintf('%d: %s', $formattedResponse['status'], $formattedResponse['detail']);
            return false;
        }

        if( $timeout > 0 && $response['headers'] && $response['headers']['total_time'] >= $timeout ) {
            $this->last_error = sprintf('Request timed out after %f seconds.', $response['headers']['total_time'] );
            return false;
        }

        $this->last_error = 'Unknown error, call getLastResponse() to find out what happened.';
        return false;
    }

    /**
     * Find the HTTP status code from the headers or API response body.
     *
     * @param   array   $response   The response from the curl request
     * @param   array|false $formattedResponse  The response body payload from the curl request
     * @return  integer     HTTP status code
     */
    private function findHTTPStatus($response, $formattedResponse)
    {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int) $response['headers']['http_code'];
        }

        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int) $formattedResponse['status'];
        }

        return 418;
    }
}

?>
