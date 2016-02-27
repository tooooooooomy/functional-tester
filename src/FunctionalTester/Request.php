<?php
namespace FunctionalTester;

class Request
{
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

    public static function parseFilePath($filepath)
    {
        $filepath = preg_replace('/\#.+\z/', '', $filepath);
        $query = [];

        preg_match('/\A(.+?)(?:\?(.*))?\z/', $filepath, $matches);
        $file = $matches[1];
        $query_string = sizeof($matches) > 2 ? $matches[2] : '';

        return [$file, $query_string];
    }

    public static function normalizeHeaderName($name)
    {
        return strtoupper(str_replace('-', '_', $name));
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
            $this->headers[self::normalizeHeaderName($k)] = $v;
        }

        $this->body = '';

        $this->initialize();
    }

    private function initialize()
    {
        if (sizeof($this->form)) {
            $this->headers[self::normalizeHeaderName('content-type')] = 'application/x-www-form-urlencoded';
        }

        // TODO: Make this request "multipart/form-data" if files are given
    }

    public function request()
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

        //TODO: Do something with result
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

        $proc = proc_open('php-cgi', $descriptor_spec, $pipes, getcwd(), $env);

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
}
