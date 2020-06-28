<?php

namespace Github\HttpClient\Plugin;

use Github\Client;
use Github\Exception\RuntimeException;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * Add authentication to the request.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Authentication implements Plugin
{
    /**
     * @var string
     */
    private $tokenOrLogin;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var string|null
     */
    private $method;

    /**
     * @param string      $tokenOrLogin GitHub private token/username/client ID
     * @param string|null $password     GitHub password/secret (optionally can contain $method)
     * @param string|null $method       One of the AUTH_* class constants
     */
    public function __construct($tokenOrLogin, $password, $method)
    {
        $this->tokenOrLogin = $tokenOrLogin;
        $this->password = $password;
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        switch ($this->method) {
            case Client::AUTH_HTTP_PASSWORD:
                @trigger_error('Using the "Client::AUTH_HTTP_PASSWORD" authentication method is deprecated in knp-labs/php-github-api v2.15 and will be removed in knp-labs/php-github-api v3.0. Use "Client::AUTH_ACCESS_TOKEN" instead.', E_USER_DEPRECATED);
            case Client::AUTH_CLIENT_ID:
                $request = $request->withHeader(
                    'Authorization',
                    sprintf('Basic %s', base64_encode($this->tokenOrLogin.':'.$this->password))
                );
                break;

            case Client::AUTH_HTTP_TOKEN:
                @trigger_error('Using the "Client::AUTH_HTTP_TOKEN" authentication method is deprecated in knp-labs/php-github-api v2.15 and will be removed in knp-labs/php-github-api v3.0. Use "Client::AUTH_ACCESS_TOKEN" instead.', E_USER_DEPRECATED);
            case Client::AUTH_ACCESS_TOKEN:
                $request = $request->withHeader('Authorization', sprintf('token %s', $this->tokenOrLogin));
                break;

            case Client::AUTH_URL_CLIENT_ID:
                @trigger_error('Using the "Client::AUTH_URL_CLIENT_ID" authentication method is deprecated in knp-labs/php-github-api v2.15 and will be removed in knp-labs/php-github-api v3.0. Use "Client::AUTH_CLIENT_ID" instead.', E_USER_DEPRECATED);

                $uri = $request->getUri();
                $query = $uri->getQuery();

                $parameters = [
                    'client_id' => $this->tokenOrLogin,
                    'client_secret' => $this->password,
                ];

                $query .= empty($query) ? '' : '&';
                $query .= utf8_encode(http_build_query($parameters, '', '&'));

                $uri = $uri->withQuery($query);
                $request = $request->withUri($uri);
                break;

            case Client::AUTH_URL_TOKEN:
                @trigger_error('Using the "Client::AUTH_URL_TOKEN" authentication method is deprecated in knp-labs/php-github-api v2.15 and will be removed in knp-labs/php-github-api v3.0. Use "Client::AUTH_ACCESS_TOKEN" instead.', E_USER_DEPRECATED);

                $uri = $request->getUri();
                $query = $uri->getQuery();

                $parameters = ['access_token' => $this->tokenOrLogin];

                $query .= empty($query) ? '' : '&';
                $query .= utf8_encode(http_build_query($parameters, '', '&'));

                $uri = $uri->withQuery($query);
                $request = $request->withUri($uri);
                break;
            case Client::AUTH_JWT:
                $request = $request->withHeader('Authorization', sprintf('Bearer %s', $this->tokenOrLogin));
                break;
            default:
                throw new RuntimeException(sprintf('%s not yet implemented', $this->method));
        }

        return $next($request);
    }
}
