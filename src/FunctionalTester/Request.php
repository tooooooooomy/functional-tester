<?php

namespace FunctionalTester;

class Request
{
    /**
     * Boundary separator for a multipart message.
     */
    const BOUNDARY = 'xYzZY';

    public static $INCLUDE_PATH = [];
    public static $INI_SET = [
        'automatically_populate_raw_post_data=-1',
    ];

    /**
     * Mandatory environmental variables in CGI.
     */
    private static $CGI_ENV_VARS = ['CONTENT_TYPE', 'CONTENT_LENGTH'];

    /**
     * HTTP request method.
     *
     * @var string
     */
    private $method;

    /**
     * HTTP request query string.
     *
     * @var string
     */
    private $query_string;

    /**
     * Executing local file.
     *
     * @var string
     */
    private $exec_file_path;

    /**
     * HTTP request content.  A form if array, else request body.
     *
     * @var array|string
     */
    private $content;

    /**
     * HTTP request headers.
     *
     * @var array
     */
    private $headers;

    /**
     * Files to be added to a request.
     *
     * @var array
     */
    private $files;

    /**
     * HTTP request body.
     *
     * @var string
     */
    private $body;

    /**
     * An accessor for private variable `method`.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * An accessor for private variable `exec_file_path`.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->exec_file_path;
    }

    /**
     * An accessor for private variable `query_string`.
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->query_string;
    }

    /**
     * An accessor for private variable `content`.
     *
     * @return array|string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * An accessor for private variable `headers`.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * An accessor for private variable `files`.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * An accessor for private variable `body`.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Appends $path into static $INCLUDE_PATH.
     *
     * @param string $path
     */
    public static function addIncludePath($path)
    {
        self::$INCLUDE_PATH[] = $path;
    }

    /**
     * Appends an option for ini_set into static $INI_SET.
     *
     * @param string $ini_set
     */
    public static function addIniSet($ini_set)
    {
        self::$INI_SET[] = $ini_set;
    }

    /**
     * Separates request file path (request URI) into `file` and `query_string`.
     *
     * @param string $filepath
     *
     * @return array
     */
    public static function parseFilePath($filepath)
    {
        $filepath = preg_replace('/\#.+\z/', '', $filepath);
        $query = [];

        preg_match('/\A(.+?)(?:\?(.*))?\z/', $filepath, $matches);
        $file = $matches[1];
        $query_string = sizeof($matches) > 2 ? $matches[2] : '';

        return [$file, $query_string];
    }

    /**
     * Normalizes HTTP header name to be passed in ENV
     * (http://www.tutorialspoint.com/perl/perl_cgi.htm).
     *
     * @param string $name
     *
     * @return string
     */
    public static function normalizeHttpHeaderName($name)
    {
        $name = strtoupper(str_replace('-', '_', $name));

        if (!in_array($name, self::$CGI_ENV_VARS)) {
            $name = 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * Builds multipart HTTP message body from form variables and attaching files.
     *
     * @param array $form
     * @param array $files
     *
     * @return string
     */
    public static function buildMultipartBody($form, $files)
    {
        $body = '';
        $boundary = self::BOUNDARY;

        foreach ($form as $k => $v) {
            $body .= <<<END
--{$boundary}
Content-Disposition: form-data; name="{$k}"

{$v}

END;
        }

        foreach ($files as $k => $file) {
            if (is_array($file)) {
                $body .= <<<END
--{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$file['name']}"
Content-Type: {$file['type']}

{$file['content']}

END;
            } elseif (is_string($file)) {
                $name = basename($file);
                $type = mime_content_type($file);
                $content = file_get_contents($file);

                $body .= <<<END
--{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$name}"
Content-Type: {$type}

{$content}

END;
            }
        }

        $body .= "--{$boundary}--";

        return $body;
    }

    /**
     * Finds installed `php-cgi` path and append $INCLUDE_PATH and $INI_SET to its path.
     *
     * @return string
     */
    public static function getPhpBin()
    {
        $php_bin = shell_exec('which php-cgi');
        $php_bin = preg_replace('/\R/', '', $php_bin);

        if (sizeof(self::$INCLUDE_PATH)) {
            $include_path = implode(':', self::$INCLUDE_PATH)
                .':'.get_include_path();
            $php_bin .= " -d include_path=\"{$include_path}\"";
        }
        if (sizeof(self::$INI_SET)) {
            foreach (self::$INI_SET as $ini_set) {
                $php_bin .= " -d {$ini_set}";
            }
        }

        return $php_bin;
    }

    /**
     * Makes a fake HTTP request to local file.
     *
     * @param array  $env
     * @param string $body
     *
     * @return array
     */
    public static function makeFakeRequest($env, $body)
    {
        $raw_stdout = '';
        $raw_stderr = '';
        $ret = 0;

        $descriptor_spec = [
            0 => ['pipe', 'r'], // STDIN  for child process
            1 => ['pipe', 'w'], // STDOUT for child process
            2 => ['pipe', 'w'], // STDERR for child process
        ];
        $pipes = [];

        $env['REDIRECT_STATUS'] = 'CGI';
        $env['CONTENT_LENGTH'] = strlen($body);

        $proc = proc_open(
            self::getPhpBin(),
            $descriptor_spec,
            $pipes,
            getcwd(),
            $env
        );

        if (is_resource($proc)) {
            fwrite($pipes[0], $body);
            fclose($pipes[0]);

            $raw_stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $raw_stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $ret = proc_close($proc);
        }

        return [$ret, $raw_stdout, $raw_stderr];
    }

    /**
     * Makes a fake HTTP response message from raw response from local file.
     *
     * @param string $raw_response
     *
     * @return string
     */
    public static function makeFakeResponse($raw_response)
    {
        list($headers, $body) = explode("\r\n\r\n", $raw_response);

        if (preg_match('/^Status\:\s([^\r\n]+)/m', $headers, $matches)) {
            $raw_response = "HTTP/1.1 {$matches[1]}\r\n".$raw_response;
        } else {
            $raw_response = "HTTP/1.1 200 OK\r\n".$raw_response;
        }

        return $raw_response;
    }

    /**
     * Creates an instance.
     *
     * @param string $method
     * @param string $exec_file_path
     * @param array  $content
     * @param array  $headers
     * @param array  $files
     */
    public function __construct($method, $exec_file_path, $content = [], $headers = [], $files = [])
    {
        $this->method = $method;
        $this->content = $content;
        $this->files = $files;

        list($this->exec_file_path, $this->query_string)
            = self::parseFilePath($exec_file_path);

        $this->headers = [];
        foreach ($headers as $k => $v) {
            $this->headers[self::normalizeHttpHeaderName($k)] = $v;
        }

        $this->initialize();
    }

    /**
     * Initializes an instance.
     */
    private function initialize()
    {
        $this->body = '';

        if (is_array($this->content) && sizeof($this->files)) {
            $this->headers[self::normalizeHttpHeaderName('content-type')]
                = 'multipart/form-data; boundary='.self::BOUNDARY;

            $this->body = self::buildMultipartBody($this->content, $this->files);
        } elseif (is_array($this->content) && sizeof($this->content)) {
            $this->headers[self::normalizeHttpHeaderName('content-type')]
                = 'application/x-www-form-urlencoded';

            $this->body = http_build_query($this->content);
        } elseif (is_string($this->content)) {
            $this->body = $this->content;
        }
    }

    /**
     * Fakes sending an HTTP request to local file,
     * and returns a bare HTTP response message in string.
     *
     * @return string
     */
    public function send()
    {
        $env = [
            'REQUEST_METHOD' => $this->method,
            'SCRIPT_FILENAME' => $this->exec_file_path,
            'QUERY_STRING' => $this->query_string,
        ];

        foreach ($this->headers as $k => $v) {
            $env[$k] = $v;
        }

        //TODO: Prepare executing file by merging bootstrap?

        list($ret, $stdout, $stderr) = $this->makeFakeRequest($env, $this->body);

        // Non-zero exit status => 500 Internal Server Error
        if ($ret) {
            throw new \Exception($stderr);
        }

        return self::makeFakeResponse($stdout);
    }
}
