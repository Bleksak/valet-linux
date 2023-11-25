<?php

class NetteValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     *
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri): bool
    {
        if (file_exists($sitePath.'/app/bootstrap.php') &&
            file_exists($sitePath.'/www/.maintenance.php')
            || file_exists($sitePath . '/src/app/bootstrap.php')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     *
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri): bool
    {
        if (file_exists($staticFilePath = $sitePath.'/www/'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    public function serveStaticFile($staticFilePath, $sitePath, $siteName, $uri): void
    {
        $path = $this->asActualFile($sitePath . '/www', $uri);
        header('Content-Type: text/html');
        header_remove('Content-Type');

        header('X-Accel-Redirect: /' . VALET_STATIC_PREFIX . $path);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     *
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

        $dynamicCandidates = [
            $this->asActualFile($sitePath, $uri),
            $this->asActualFile($sitePath . "/www", $uri),
            $this->asPhpIndexFileInDirectory($sitePath, $uri),
            $this->asHtmlIndexFileInDirectory($sitePath, $uri),
        ];

        foreach ($dynamicCandidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                $_SERVER['DOCUMENT_ROOT'] = $sitePath;

                return $candidate;
            }
        }

        $fixedCandidatesAndDocroots = [
            $this->asRootPhpIndexFile($sitePath) => $sitePath,
            $this->asPublicPhpIndexFile($sitePath) => $sitePath . '/www',
            $this->asPublicHtmlIndexFile($sitePath) => $sitePath . '/www',
        ];

        foreach ($fixedCandidatesAndDocroots as $candidate => $docroot) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = '/index.php';
                $_SERVER['DOCUMENT_ROOT'] = $docroot;

                return $candidate;
            }
        }
    }

    /**
     * Concatenate the site path and URI as a single file name.
     *
     * @param string $sitePath
     * @param string $uri
     * @return string
     */
    protected function asActualFile($sitePath, $uri): string
    {
        return $sitePath . $uri;
    }

    /**
     * Format the site path and URI with a trailing "index.php".
     *
     * @param string $sitePath
     * @param string $uri
     * @return string
     */
    protected function asPhpIndexFileInDirectory($sitePath, $uri): string
    {
        return $sitePath . rtrim($uri, '/') . '/index.php';
    }

    /**
     * Format the site path and URI with a trailing "index.html".
     *
     * @param string $sitePath
     * @param string $uri
     * @return string
     */
    protected function asHtmlIndexFileInDirectory($sitePath, $uri): string
    {
        return $sitePath . rtrim($uri, '/') . '/index.html';
    }

    /**
     * Format the incoming site path as root "index.php" file path.
     *
     * @param string $sitePath
     * @return string
     */
    protected function asRootPhpIndexFile($sitePath): string
    {
        return $sitePath . '/index.php';
    }

    /**
     * Format the incoming site path as a "www/index.php" file path.
     *
     * @param string $sitePath
     * @return string
     */
    protected function asPublicPhpIndexFile($sitePath): string
    {
        return $sitePath . '/www/index.php';
    }

    /**
     * Format the incoming site path as a "www/index.php" file path.
     *
     * @param string $sitePath
     * @return string
     */
    protected function asPublicHtmlIndexFile($sitePath): string
    {
        return $sitePath . '/www/index.html';
    }
}
