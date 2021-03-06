<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Exception\BadResponseException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as BaseException;

/**
 * Base wrapper for exceptions thrown by the Guzzle library
 */
class GuzzleRestException extends BaseException
{
    /**
     * @param \Exception $exception
     * @return GuzzleRestException
     */
    public static function createFromException(\Exception $exception)
    {
        if ($exception instanceof BadResponseException && $exception->getResponse()) {
            $url = $exception->getRequest() ? (string)$exception->getRequest()->getUri() : null;
            $result = GuzzleRestException::createFromResponse(
                new GuzzleRestResponse($exception->getResponse(), $url),
                null,
                $exception
            );
        } else {
            /** @var GuzzleRestException $result */
            $result = new static($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $result;
    }
}
