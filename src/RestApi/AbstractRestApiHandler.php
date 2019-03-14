<?php

namespace RebelCode\Wpra\Core\RestApi;

use Dhii\Transformer\TransformerInterface;
use Exception;
use InvalidArgumentException;
use RebelCode\Wpra\Core\Transformers\RecursiveToArrayTransformer;
use Traversable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Abstract functionality for REST API handlers.
 *
 * @since [*next-version*]
 */
abstract class AbstractRestApiHandler
{
    /**
     * Invokes the handler.
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $request = func_get_arg(0);

        if (!($request instanceof WP_REST_Request)) {
            throw new InvalidArgumentException('Argument is not a WP_REST_Request instance');
        }

        try {
            // Handle the request and get the response
            $response = $this->handle($request);
        } catch (Exception $exception) {
            // In the event of an exception, the response is set to be a 500 Internal Server error
            $response = new WP_Error('wprss_rest_api_error', $exception->getMessage(), ['status' => 500]);
        }


        // If the response is an error or no transformer should be used, return the response "as is"
        if ($response instanceof WP_Error) {
            return $response;
        }

        // Retrieve the data
        $data = $response->get_data();
        // Turn the data into an array if it's a traversable
        $aData = ($data instanceof Traversable)
            ? iterator_to_array($data)
            : $data;

        // Transform the data if a transformer is given
        $transformer = $this->getTransformer();
        $tData = ($transformer instanceof TransformerInterface)
            ? $transformer->transform($aData)
            : $aData;

        // Update the response with the transformed data
        $response->set_data($tData);

        return $response;
    }

    /**
     * Retrieves the response transformer to use, if any.
     *
     * @since [*next-version*]
     *
     * @return TransformerInterface|null The transformer instance or null if no transformer is required.
     */
    protected function getTransformer()
    {
        return new RecursiveToArrayTransformer();
    }

    /**
     * Handles the request and provides a response.
     *
     * @since [*next-version*]
     *
     * @param WP_REST_Request $request The request.
     *
     * @return WP_REST_Response|WP_Error The response or error.
     */
    abstract protected function handle(WP_REST_Request $request);
}
