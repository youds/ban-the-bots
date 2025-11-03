<?php

class BanTheBots
{

    static private $API_BASE_URL = 'https://api.banthebots.cloud';
    static private $DEFAULT_CHECK_INTERVAL = 4;
    static private $DEFAULT_ROBOTS_PATH = '/';
    private $checkInterval;
    private $robotsPath;
    private $blackholePath;

    static private $BAN_MESSAGE;

    public function __construct(array $config = [])
    {
        self::$BAN_MESSAGE = '
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .error-container {
        text-align: center;
        padding: 2rem;
        max-width: 600px;
        width: 90%;
    }

    .error-icon {
        font-size: 5rem;
        color: #dc3545;
        margin-bottom: 1.5rem;
    }

    .error-title {
        font-size: 2rem;
        color: #333;
        margin-bottom: 1rem;
    }

    .error-message {
        font-size: 1.1rem;
        color: #666;
        line-height: 1.5;
        margin-bottom: 2rem;
    }

    .error-button {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .error-button:hover {
        background-color: #0056b3;
    }

    @media (max-width: 480px) {
        .error-container {
            padding: 1.5rem;
        }

        .error-icon {
            font-size: 4rem;
        }

        .error-title {
            font-size: 1.5rem;
        }

        .error-message {
            font-size: 1rem;
        }
    }
</style>

<div class="error-container">
    <div class="error-icon">⚠️</div>
    <h1 class="error-title">Oops! This request looks like you\'re a bot</h1>
    <p class="error-message">
        We apologise, but an error has occurred while processing your request.
        Please try again later or contact support if the problem persists.
    </p>
    <a href="https://www.banthebots.cloud/" class="error-button">Return Home</a>
</div>';


    public function __construct(array $config = [])
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // config
        $this->checkInterval = isset($config['checkInterval']) ? $config['checkInterval'] : self::$DEFAULT_CHECK_INTERVAL;
        $this->robotsPath = isset($config['robotsPath']) ? $config['robotsPath'] : self::$DEFAULT_ROBOTS_PATH;

        // setup our environment
        $robotUrl = $this->robotsPath . '.robotUrl.php';
        if (!is_readable($robotUrl))
            file_put_contents($robotUrl, sprintf('<?php
$blackholePath = \'%s\';
?>', substr(md5(uniqid(rand(), true)), 0, rand(1, 32)) . '.php'));

        if (is_readable($robotUrl))
            include($this->robotsPath . '.robotUrl.php'); // retrieve $blackholePath
        $this->blackholePath = $blackholePath;

        # Blackhole Path
        if (!is_file($this->robotsPath . $blackholePath))
            file_put_contents($this->robotsPath . $blackholePath, '<?php
file_get_contents(sprintf(
    \'%s/v1/bad-bots/create?ip=%s&userAgent=%s\',
    \'' . self::$API_BASE_URL . '\',
    $_SERVER[\'REMOTE_ADDR\'],
    base64_encode($_SERVER[\'HTTP_USER_AGENT\'])
));
header(\'HTTP/1.1 403 Forbidden\');
header(\'Location: ' . self::$API_BASE_URL . '/blackhole?returnTo=\' . $_SERVER[\'SERVER_NAME\'] . str_replace(\'' . $blackholePath . '\', \'\', $_SERVER[\'REQUEST_URI\']));
die(\'' . addslashes(self::$BAN_MESSAGE) . '\');
?>');

        $robotsPath = $this->robotsPath . 'robots.txt';
        if (!is_readable($_SERVER['DOCUMENT_ROOT'] . $this->robotsPath) || !strstr(file_get_contents($robotsPath), $this->robotsPath)):
            file_put_contents(
                $this->robotsPath . 'robots.txt', '
User-agent: *
Disallow: /' . $blackholePath . '
'
            );
        endif;

    }

    private function checkSession()
    {
        if ((isset($_SESSION['banned']) || !isset($_SESSION['visited']) || ($_SESSION['visited'] + $this->checkInterval < time()))) {
            $badBotUrl = sprintf(
                '%s/v1/bad-bots/read?ip=%s&userAgent=%s',
                self::$API_BASE_URL,
                $_SERVER['REMOTE_ADDR'],
                base64_encode($_SERVER['HTTP_USER_AGENT'])
            );
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $badBotUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    throw new \Exception('Curl error: ' . curl_error($ch));
                }

                curl_close($ch);
                $badBotsApi = json_decode($response);

                // debug
                //dump($badBotsApi);

                if ($badBotsApi && isset($badBotsApi->badBot) && $badBotsApi->badBot === true) {
                    $_SESSION['banned'] = time();
                    return true;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
            if (isset($_SESSION['banned']))
                unset($_SESSION['banned']);
            $_SESSION['visited'] = time();
        }
        return false;
    }

    public function apply()
    {
        if ($this->checkSession() && (isset($_SESSION['banned']))) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: ' . self::$API_BASE_URL . '/blackhole?returnTo=' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
            die(self::$BAN_MESSAGE);
        }

    }

    public function outputBlackHole()
    {
        echo '<span style="display:none"><a href="https://' . $_SERVER['SERVER_NAME'] . '/' . $this->blackholePath . '">Blackhole</a><!--Ban The Bots--><a href="https://www.banthebots.cloud/">Ban The Bots</a> courtesy of <a href="https://www.youds.com/">Youds Media Limited</a></span>';
    }

}

