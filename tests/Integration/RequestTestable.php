<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Http\Request;

/**
 * Class RequestTestable
 * Trait to easy get an istance of Illuminate\Http\Request and bint it for testing.
 * @package Padosoft\Laravel\Request
 */
trait RequestTestable
{
    /**
     * Create an instance of \Illuminate\Http\Request for testing.
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     * @return \Illuminate\Http\Request
     */
    public function getRequestForTest(
        $uri = '/',
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) : \Illuminate\Http\Request
    {
        //create request
        return Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Create an instance of Illuminate\Http\Request for testing.
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     * @return \Illuminate\Http\Request
     */
    public function getRequestAndBindItForTest(
        $uri = '/',
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) : \Illuminate\Http\Request
    {
        //create request
        $request = $this->getRequestForTest($uri, $method, $parameters, $cookies, $files, $server, $content);

        //bind request istance and facade to this class
        $this->bindRequestForTest($request);

        //return the request
        return $request;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function bindRequestForTest(\Illuminate\Http\Request $request)
    {
        //bind request istance to this class
        $this->app->instance(Request::class, $request);
        //bind request facade to this istance
        $this->app->instance('request', $request);
    }

    /**
     * Get request for upload file and bind.
     * Usefull for testing upload.
     * @param array $arrUploadedFiles are in the form ['image' => $UploadFile1, 'file' => $UploadFile2,....]
     * where $UploadFile1 and $uploadFile2 are istance of \Illuminate\Http\UploadedFile.
     * @return \Illuminate\Http\Request
     */
    public function getRequestAndBindItForUploadTest(array $arrUploadedFiles) : \Illuminate\Http\Request
    {
        //create request with uploaded files
        return $this->getRequestAndBindItForTest('/', 'POST', [], [], $arrUploadedFiles);
    }
}
