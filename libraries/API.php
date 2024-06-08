<?php

namespace packages\kavenegar_smsgateway;

use packages\base\Exception;
use packages\base\HTTP;
use packages\base\Json;
use packages\base\Log;
use packages\kavenegar_smsgateway\GateWay\GateWayException;
use packages\sms\GateWay;
use packages\sms\GateWay\Handler as ParentHandler;
use packages\sms\Sent;

use function packages\base\Utility\GetTelephoneWithDialingCode;

/**
 * @template T
 *
 * @phpstan-type ResponseType array{
 *      'return':ReturnType,
 *      'entries':T
 * }
 * @phpstan-type ReturnType array{
 *      'status':int,
 *      'message':string,
 * }
 * @phpstan-type SendEntryType array{
 *      'messageid':int,
 *      'message':string,
 *      'status':int,
 *      'statustext':string,
 *      'sender':string,
 *      'receptor':string,
 *      'date':int,
 *      'cost':float
 * }
 * @phpstan-type AccountInfoType array{
 *      'remaincredit':int,
 *      'expiredate':int,
 *      'type':'Master'|string
 * }
 */
class API extends ParentHandler
{
    public const GATEWAY_NAME = 'kavenegar';

    protected const BASE_URI = 'https://api.kavenegar.com/v1';

    /**
     * @return ResponseType
     */
    protected static function getDecodedBodyFromResponse(HTTP\Response $response): ?array
    {
        $body = $response->getBody();

        return null === $body ? null : Json\decode($body);
    }

    protected string $apiKey;

    protected ?HTTP\Client $httpClient = null;

    public function __construct(GateWay $gateway)
    {
        $apiKey = $gateway->param('kavenegar_apikey');
        if (!$apiKey) {
            throw new Exception('KaveNegar: apikey is missing!');
        }
        $this->apiKey = $apiKey;
    }

    public function getHttpClient(): HTTP\Client
    {
        if (!$this->httpClient) {
            $this->httpClient = new HTTP\Client([
                'base_uri' => self::BASE_URI.'/'.$this->apiKey.'/',
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'PHP with Jalno',
                ],
            ]);
        }

        return $this->httpClient;
    }

    /**
     * @return AccountInfoType|null
     */
    public function getAccountInfo(): ?array
    {
        $log = Log::getInstance();
        $response = $this->getHttpClient()->get('account/info.json');
        /** @var ResponseType<AccountInfoType> */
        $result = self::getDecodedBodyFromResponse($response);
        if ($result) {
            return $result['entries'];
        }

        return null;
    }

    /**
     * try to send SMS to given number.
     *
     * @return int that may be Sent::sent or Sent::failed that indicates the result
     *
     * @throws GateWayException on error
     */
    public function send(Sent $sms)
    {
        $log = Log::getInstance();
        $receptor = $this->convertReceiverNumber($sms->receiver_number);
        $params = [
            'receptor' => $receptor,
            'message' => $sms->text,
            'sender' => $sms->sender_number->number,
        ];

        $body = null;
        try {
            $log->debug(self::GATEWAY_NAME.": send SMS to {$receptor} from sender {$sms->sender_number->number}");
            $response = $this->getHttpClient()->post('sms/send.json', [
                'form_params' => $params,
            ]);

            $result = self::getDecodedBodyFromResponse($response);

            if (null === $result) {
                return Sent::failed;
            }

            if (200 == $result['return']['status']) {
                $log->reply('done', $result);

                $entry = array_shift($result['entries']);

                $sms->setParam(self::GATEWAY_NAME.'_messageid', $entry['messageid']);
                $sms->setParam(self::GATEWAY_NAME.'_result_metadata', Json\encode([
                    'status' => $entry['status'],
                    'cost' => $entry['cost'],
                ]));

                return Sent::sent;
            }
        } catch (HTTP\ResponseException $e) {
            $log->reply()->error('faild! exception: '.get_class($e), 'request:', $e->getRequest(), 'response:', $e->getResponse());
            throw new GateWayException($e->getResponse()->getBody(), $e);
        } catch (Json\JsonException $e) {
            $log->reply()->error('faild! json exception: body: '.$body);
            throw new GateWayException($body, $e);
        }

        return Sent::failed;
    }

    /**
     * @param string $number that is something like this: IR.9387654321
     *
     * @return string that is converted to this: 989387654321
     */
    protected function convertReceiverNumber(string $number): string
    {
        return str_replace('.', '', getTelephoneWithDialingCode($number));
    }
}
