<?php
namespace FunctionalTester;

use Guzzle\Http\Message\Response;

class FunctionalTester
{
    protected $env = [];
    protected $documentRoot;
    protected $includePath;
    protected $phpOptions;

    /**
     * @param string $documentRoot
     * @param string $includePath
     */
    public function __construct($documentRoot = '/', $includePath = '.:/usr/share/pear:/usr/share/php')
    {
        $this->documentRoot = $documentRoot;
        $this->includePath = $includePath;
        $this->phpOptions = [ //default options
            'display_errors' => 0,
            'error_reporting' => 0,
        ];
    }

    public function getIncludePath()
    {
        return $this->includePath;
    }

    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    public function setIncludePath($includePath)
    {
        $this->includePath = $includePath;
    }

    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    public function addIncludePath($path)
    {
        $this->includePath .= $path;
    }

    public function setPhpOptions($options)
    {
        $this->phpOptions = $options;
    }

    public function getPhpOptions()
    {
        return $this->phpOptions;
    }

    /**
     * @param $method
     * @param $file
     * @param null $parameters
     * @param null $options
     * @return bool|Response
     */
    public function request($method, $file, $parameters = null, $options = null)
    {
        $paramStr = ($parameters) ? http_build_query($parameters) : "";

        $defaultOptions = [
            'SCRIPT_FILENAME' => $this->documentRoot . $file,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'REQUEST_METHOD' => $method,
            'CONTENT_LENGTH' => strlen($paramStr),
            'REDIRECT_STATUS' => 'CGI',
        ];

        $this->setEnv($defaultOptions);
        if ($options) {
            $this->setEnv($options);
        }

        $envStr = $this->makeEnvString();
        $phpOptionsStr = $this->makePhpOptionsString();
        $response = $this->send($paramStr, $envStr, $phpOptionsStr);
        $adjustedResponse = $this->setHttpProtocolToResponse($response);

        return $this->parseResponse($adjustedResponse);
    }

    /**
     * @param $paramStr
     * @param $envStr
     * @return string
     */
    public function send($paramStr, $envStr, $phpOptionsStr)
    {
        return shell_exec("echo '$paramStr' | env $envStr php-cgi -d include_path=$this->includePath $phpOptionsStr");
    }

    /**
     * @param $file
     * @param null $parameters
     * @param null $options
     * @return bool|Response
     */
    public function get($file, $parameters = null, $options = null)
    {
        if ($parameters) {
            $this->env['QUERY_STRING'] = http_build_query($parameters);
        }

        return $this->request('GET', $file, $parameters, $options);
    }

    /**
     * @param $file
     * @param null $parameters
     * @param null $options
     * @return bool|Response
     */
    public function post($file, $parameters = null, $options = null)
    {
        return $this->request('POST', $file, $parameters, $options);
    }

    /**
     * @return string
     */
    public function makeEnvString()
    {
        $array = [];
        foreach ($this->env as $key => $value) {
            array_push($array, "$key='$value'");
        }

        return implode(' ', $array);
    }

    /**
     * @param array $options
     */
    public function setEnv(array $options)
    {
        foreach ($options as $key => $value) {
            $this->env[$key] = $value;
        }
    }

    /**
     * @param null $optionNames
     * @return array
     */
    public function getEnv($optionNames = null)
    {
        $env = [];
        if ($optionNames) {
            foreach ($optionNames as $name) {
                $env[$name] = $this->env[$name];
            }
        } else {
            $env = $this->env;
        }

        return $env;
    }

    /**
     * @param $response
     * @return string
     */
    public function setHttpProtocolToResponse($response)
    {
        $lines = preg_split('/(\\r?\\n)/', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
        $parts = explode(':', $lines[0], 2);
        $startLine = $parts[0] == 'Status' ? "HTTP/1.1" . $parts[1] . "\r\n" : "HTTP/1.1 200 OK";

        return $startLine . $response;
    }

    /**
     * @param $response
     * @return bool|Response
     */
    public function parseResponse($response)
    {
        return Response::fromMessage($response);
    }

    /**
     * @param array $parameters
     * @param string $name
     */
    public function setSession(array $parameters, $name = 'PHPSESSID')
    {
        session_name($name);
        session_start();

        foreach ($parameters as $key => $value) {
            $_SESSION[$key] = $value;
        }

        if (isset($this->env['HTTP_COOKIE'])) {
            session_regenerate_id();
            $this->env['HTTP_COOKIE'] .= ";$name=" . session_id();
        } else {
            $this->env['HTTP_COOKIE'] = "$name=" . session_id();
        }
        session_write_close();
    }

    /**
     * @param string $name
     */
    public function initializeSession($name = 'PHPSESSID')
    {
        session_name($name);
        session_start();
        session_destroy();
    }

    public function makePhpOptionsString()
    {
        $array = [];
        foreach ($this->phpOptions as $key => $value) {
            array_push($array, "-d $key='$value'");
        }

        return implode(' ', $array);
    }
}
