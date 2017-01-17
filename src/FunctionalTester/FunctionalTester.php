<?php
namespace FunctionalTester;

use FunctionalTester\Message\MessageFactory;
use FunctionalTester\Message\Response;

class FunctionalTester
{
    /**
     * @var array
     */
    protected $env = [];

    /**
     * @var string
     */
    protected $documentRoot;

    /**
     * @var string
     */
    protected $includePath;

    /**
     * @var array
     */
    protected $phpOptions = [];

    /**
     * @var string
     */
    protected $boundary = 'Boundary';

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @param string $documentRoot
     * @param string $includePath
     * @param MessageFactory|null $messageFactory
     */
    public function __construct($documentRoot = '/', $includePath = '.:/usr/share/pear:/usr/share/php', MessageFactory $messageFactory=null)
    {
        $this->documentRoot = $documentRoot;
        $this->includePath = $includePath;
        $this->messageFactory = $messageFactory ?: new MessageFactory();
    }

    /**
     * @return string
     */
    public function getIncludePath()
    {
        return $this->includePath;
    }

    /**
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    /**
     * @param string $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->includePath = $includePath;
    }

    /**
     * @param string $documentRoot
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * @param string $path
     */
    public function addIncludePath($path)
    {
        $this->includePath .= $path;
    }

    /**
     * @param array $options
     */
    public function setPhpOptions($options)
    {
        $this->phpOptions = $options;
    }

    /**
     * @return array
     */
    public function getPhpOptions()
    {
        return $this->phpOptions;
    }

    /**
     * @param string $method
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @param null $files
     * @param null|string $content
     * @return Response
     */
    public function request($method, $scriptFile, $parameters = null, $options = null, $files = null, $content = null)
    {
        $execFile = $this->generateExecFile($scriptFile);

        $defaultOptions = [
            'SCRIPT_FILENAME' => $execFile,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'REQUEST_METHOD' => $method,
            'REDIRECT_STATUS' => 'CGI',
        ];

        if ($files) {
            $reqBody = $this->generateStringForMultiPart($parameters, $files);
            $defaultOptions['CONTENT_TYPE'] = 'multipart/form-data; boundary=' . $this->boundary;
        } elseif ($content) {
            $reqBody = $content;
        } else {
            $reqBody = ($parameters) ? http_build_query($parameters) : "";
        }

        $defaultOptions['CONTENT_LENGTH'] = strlen($reqBody);

        $this->setEnv($defaultOptions);
        if ($options) {
            $this->setEnv($options);
        }

        $envStr = $this->makeEnvString();
        $phpOptionsStr = $this->makePhpOptionsString();
        $responseMessage = $this->send($reqBody, $envStr, $phpOptionsStr);

        unlink($execFile);

        return $this->messageFactory->fromMessage($responseMessage);
    }

    /**
     * @param string $reqBody
     * @param string $envStr
     * @return string
     */
    public function send($reqBody, $envStr, $phpOptionsStr)
    {
        $tmpFileName = tempnam(__DIR__ . '/tmp', 'prefix');
        file_put_contents($tmpFileName, $reqBody);
        $result =  shell_exec("cat $tmpFileName | env $envStr php-cgi -d include_path=$this->includePath $phpOptionsStr");
        unlink($tmpFileName);

        return $result;
    }

    /**
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @return Response
     */
    public function get($scriptFile, $parameters = null, $options = null)
    {
        if ($parameters) {
            $this->env['QUERY_STRING'] = http_build_query($parameters);
        }

        return $this->request('GET', $scriptFile, null, $options);
    }

    /**
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @param null|array $files
     * @return Response
     */
    public function post($scriptFile, $parameters = null, $options = null, $files= null)
    {
        if (preg_match('/(.*)\?(.+)/', $scriptFile, $matches)) {
            $this->env['QUERY_STRING'] = $matches[2];
            $scriptFile = $matches[1];
        }

        return $this->request('POST', $scriptFile, $parameters, $options, $files);
    }

    /**
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @param null|array $files
     * @return Response
     */
    public function delete($scriptFile, $parameters = null, $options = null, $files= null)
    {
        if (preg_match('/(.*)\?(.+)/', $scriptFile, $matches)) {
            $this->env['QUERY_STRING'] = $matches[2];
            $scriptFile = $matches[1];
        }

        return $this->request('DELETE', $scriptFile, $parameters, $options, $files);
    }

    /**
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @param null|array $files
     * @return Response
     */
    public function put($scriptFile, $parameters = null, $options = null, $files= null)
    {
        if (preg_match('/(.*)\?(.+)/', $scriptFile, $matches)) {
            $this->env['QUERY_STRING'] = $matches[2];
            $scriptFile = $matches[1];
        }

        return $this->request('PUT', $scriptFile, $parameters, $options, $files);
    }

    /**
     * @param string $scriptFile
     * @param null|array $parameters
     * @param null|array $options
     * @param null|array $files
     * @return Response
     */
    public function patch($scriptFile, $parameters = null, $options = null, $files= null)
    {
        if (preg_match('/(.*)\?(.+)/', $scriptFile, $matches)) {
            $this->env['QUERY_STRING'] = $matches[2];
            $scriptFile = $matches[1];
        }

        return $this->request('PATCH', $scriptFile, $parameters, $options, $files);
    }

    /**
     * @param string $method
     * @param string $scriptFile
     * @param null|array $data
     * @param null|array $options
     * @return Response
     */
    public function json($method, $scriptFile, $data = null, $options = null)
    {
        if (preg_match('/(.*)\?(.+)/', $scriptFile, $matches)) {
            $this->env['QUERY_STRING'] = $matches[2];
            $scriptFile = $matches[1];
        }

        $options['CONTENT_TYPE'] = 'application/json';

        return $this->request($method, $scriptFile, null, $options, null, json_encode($data));
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
     * @param null|array $optionNames
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
     * @return string
     */
    public function getSessionId()
    {
        return session_id();
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

    /**
     * @return string
     */
    public function makePhpOptionsString()
    {
        $array = [];
        foreach ($this->phpOptions as $key => $value) {
            array_push($array, "-d $key='$value'");
        }

        return implode(' ', $array);
    }

    /**
     * @param $scriptFile
     * @return string
     */
    public function generateExecFile($scriptFile)
    {
        $execFileName = tempnam(dirname($this->documentRoot . $scriptFile), 'prefix');
        $mockFileStr = $this->generateMockFilesStr();
        $bootstrap = __DIR__ . "/bootstrap.php";
        shell_exec("cat $mockFileStr $bootstrap $this->documentRoot$scriptFile > $execFileName");

        return $execFileName;
    }

    /**
     * @return string
     */
    public function generateMockFilesStr()
    {
        $files = scandir(__DIR__ . '/Mock');

        unset($files[0]); //'.'
        unset($files[1]); //'..'
        $dir_added_files = [];
        foreach ($files as $file) {
            $dir_added_files[] = __DIR__ . '/Mock/' . $file;
        }

        return implode(' ', $dir_added_files);
    }

    /**
     * @param null|array $parameters
     * @param null|array $files
     * @return string
     */
    public function generateStringForMultiPart($parameters=null, $files=null)
    {
        $string = '';

        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $string .= <<<EOI
--$this->boundary
Content-Disposition: form-data; name="$key"

$value

EOI;
            }
        }

        if ($files) {
            foreach ($files as $file) {
                $name = $file['name'];
                $filename = $file['filename'];
                $contents = $file['contents'];
                $type     = $file['type'];

                $string .= <<<EOI
--$this->boundary
Content-Disposition: form-data; name="$name"; filename="$filename"
Content-Type: $type

$contents

EOI;
            }
        }

        if ($string != '') {
            $string .="--$this->boundary--";
        }

        return $string;
    }
}
