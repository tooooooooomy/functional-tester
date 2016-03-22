<?php
namespace FunctionalTester;

class Request
{
    const BOUNDARY = 'xYzZY';

    public static $INCLUDE_PATH = [];

    private static $CGI_ENV_VARS = ['CONTENT_TYPE', 'CONTENT_LENGTH'];

    private
        $method,
        $query_string,
        $exec_file_path,
        $form,
        $headers,
        $files,
        $body;

    public function getMethod()   { return $this->method; }
    public function getFilePath() { return $this->exec_file_path; }
    public function getQueryString() { return $this->query_string; }
    public function getForm()     { return $this->form; }
    public function getHeaders()  { return $this->headers; }
    public function getFiles()    { return $this->files; }
    public function getBody()     { return $this->body; }

    public static function addIncludePath($path)
    {
        self::$INCLUDE_PATH[] = $path;
    }

    public static function parseFilePath($filepath)
    {
        $filepath = preg_replace('/\#.+\z/', '', $filepath);
        $query = [];

        preg_match('/\A(.+?)(?:\?(.*))?\z/', $filepath, $matches);
        $file = $matches[1];
        $query_string = sizeof($matches) > 2 ? $matches[2] : '';

        return [$file, $query_string];
    }

    # http://www.tutorialspoint.com/perl/perl_cgi.htm
    public static function normalizeHttpHeaderName($name)
    {
        $name = strtoupper(str_replace('-', '_', $name));

        if (!in_array($name, self::$CGI_ENV_VARS)) {
            $name = 'HTTP_' . $name;
        }

        return $name;
    }

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
                $name    = basename($file);
                $type    = mime_content_type($file);
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

        $php_bin = shell_exec('which php-cgi');
        $php_bin = preg_replace('/\R/', '', $php_bin);

        if (sizeof(self::$INCLUDE_PATH)) {
            $include_path = implode(':', self::$INCLUDE_PATH)
                . ':' . get_include_path();
            $php_bin .= " -d include_path=\"{$include_path}\"";
        }

        $proc = proc_open(
            $php_bin,
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

    public static function makeFakeResponse($raw_response)
    {
        list($headers, $body) = explode("\r\n\r\n", $raw_response);

        if (preg_match('/^Status\:\s([^\r\n]+)/m', $headers, $matches)) {
            $raw_response = "HTTP/1.1 {$matches[1]}\r\n" . $raw_response;
        }
        else {
            $raw_response = "HTTP/1.1 200 OK\r\n" . $raw_response;
        }

        return $raw_response;
    }

    public function __construct($method, $exec_file_path, $form = [], $headers = [], $files = [])
    {
        $this->method = $method;
        $this->form   = $form;
        $this->files  = $files;

        list($this->exec_file_path, $this->query_string)
            = self::parseFilePath($exec_file_path);

        $this->headers = [];
        foreach ($headers as $k => $v) {
            $this->headers[self::normalizeHttpHeaderName($k)] = $v;
        }

        $this->initialize();
    }

    private function initialize()
    {
        $this->body = '';

        if (sizeof($this->form)) {
            $this->headers[self::normalizeHttpHeaderName('content-type')]
                = 'application/x-www-form-urlencoded';

            $this->body = http_build_query($this->form);
        }

        if (sizeof($this->files)) {
            $this->headers[self::normalizeHttpHeaderName('content-type')]
                = 'multipart/form-data; boundary=' . self::BOUNDARY;

            $this->body = self::buildMultipartBody($this->form, $this->files);
        }
    }

    public function send()
    {
        $env = [
            'REQUEST_METHOD'  => $this->method,
            'SCRIPT_FILENAME' => $this->exec_file_path,
            'QUERY_STRING'    => $this->query_string,
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
