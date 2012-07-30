<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2010 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeHttp
{
    /**
     * execute HTTP Request
     *
     * @param string $method 
     * @param string $url 
     * @param mixed $query_data string or array
     * @param mixed $body string or array
     * @param array $headers 
     * @param array $options 
     * @return string
     */
    public static function request($method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $method = strtoupper($method);

        $_options = array(
            'method' => $method,
            'user_agent' => 'pake '.pakeApp::VERSION,
            'ignore_errors' => true,
        );

        $parsed_url = parse_url($url);

        $proxy_var_name = null;

        if ($parsed_url['scheme'] == 'http') {
            $proxy_var_name = 'http_proxy';
        } elseif ($parsed_url['scheme'] == 'https') {
            if (isset($_SERVER['https_proxy'])) {
                $proxy_var_name = 'https_proxy';
            } elseif (isset($_SERVER['HTTPS_PROXY'])) {
                $proxy_var_name = 'HTTPS_PROXY';
            }
        }

        if (null !== $proxy_var_name) {
            if (isset($_SERVER[$proxy_var_name])) {
                $parsed_proxy_str = parse_url($_SERVER[$proxy_var_name]);

                if (is_array($parsed_proxy_str) and
                    $parsed_proxy_str['scheme'] == 'http' and
                    isset($parsed_proxy_str['host']) and
                    isset($parsed_proxy_str['port'])
                ) {
                    $_options['proxy'] = 'tcp://'.$parsed_proxy_str['host'].':'.$parsed_proxy_str['port'];
                    $_options['request_fulluri'] = true;
                    pake_echo_comment('(using proxy: '.$parsed_proxy_str['host'].':'.$parsed_proxy_str['port'].')');
                } else {
                    pake_echo_error('"'.$proxy_var_name.'" environment variable is set to the wrong value. expecting http://host:port');
                }
            }
        }

        if (null !== $body) {
            if (is_array($body)) {
                $body = http_build_query($body);
            }

            $_options['content'] = $body;
        }

        if (count($headers) > 0) {
            $_options['header'] = implode("\r\n", $headers)."\r\n";
        }

        $options = array_merge($_options, $options);

        if (null !== $query_data) {
            if (is_array($query_data)) {
                $query_data = http_build_query($query_data);
            }

            $url .= '?'.$query_data;
        }

        $context = stream_context_create(array('http' => $options));

        pake_echo_action('HTTP '.$method, $url);
        $stream = @fopen($url, 'r', false, $context);

        if (false === $stream) {
            $err = error_get_last();
            throw new pakeException('HTTP request failed: '.$err['message']);
        }

        $meta = stream_get_meta_data($stream);
        $response = stream_get_contents($stream);

        fclose($stream);

        $status = $meta['wrapper_data'][0];
        $code = substr($status, 9, 3);

        if ($code > 400)
            throw new pakeException('http request returned: '.$status);

        pake_echo_action('…', 'got '.strlen($response).' bytes');

        return $response;
    }

    /**
     * execute HTTP Request and match response against PCRE regexp
     *
     * @param string $regexp PCRE regexp
     * @param string $method 
     * @param string $url 
     * @param mixed $query_data string or array
     * @param mixed $body string or array
     * @param array $headers 
     * @param array $options 
     * @return void
     */
    public static function matchRequest($regexp, $method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $response = self::request($method, $url, $query_data, $body, $headers, $options);

        $result = preg_match($regexp, $response);

        if (false === $result) {
            throw new pakeException("There's some error with this regular expression: ".$regexp);
        }

        if (0 === $result) {
            throw new pakeException("HTTP Response didn't match against regular expression: ".$regexp);
        }

        pake_echo_comment('HTTP response matched against '.$regexp);
    }


    // Convenience wrappers follow
    public static function get($url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::request('GET', $url, $query_data, null, $headers, $options);
    }

    public static function matchGet($regexp, $url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'GET', $url, $query_data, null, $headers, $options);
    }

    public static function post($url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::request('POST', $url, $query_data, $body, $headers, $options);
    }

    public static function matchPost($regexp, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'POST', $url, $query_data, $body, $headers, $options);
    }
}
