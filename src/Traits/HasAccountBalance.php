<?php

namespace MesaSDK\PhpMpesa\Traits;

trait HasAccountBalance
{
    private string $accountBalanceInitiator;
    private string $accountBalancePartyA;
    private string $accountBalanceIdentifierType = '4';
    private string $accountBalanceRemarks = 'Balance check';
    private string $accountBalanceOriginatorConversationId;

    public function setAccountBalanceInitiator(string $initiator): self
    {
        $this->accountBalanceInitiator = $initiator;
        return $this;
    }

    public function setAccountBalancePartyA(string $partyA): self
    {
        $this->accountBalancePartyA = $partyA;
        return $this;
    }

    public function setAccountBalanceIdentifierType(string $identifierType): \MesaSDK\PhpMpesa\Base\BaseMpesa
    {
        $this->accountBalanceIdentifierType = $identifierType;
        return $this;
    }

    public function setAccountBalanceRemarks(string $remarks): \MesaSDK\PhpMpesa\Base\BaseMpesa
    {
        $this->accountBalanceRemarks = $remarks;
        return $this;
    }

    public function setAccountBalanceOriginatorId(string $originatorId): self
    {
        $this->accountBalanceOriginatorConversationId = $originatorId;
        return $this;
    }

    private function validateAccountBalanceFields(): void
    {
        $required = [
            'accountBalanceInitiator' => $this->accountBalanceInitiator ?? null,
            'accountBalancePartyA' => $this->accountBalancePartyA ?? null,
            'securityCredential' => $this->getSecurityCredential(),
            'queueTimeoutUrl' => $this->getQueueTimeoutUrl(),
            'resultUrl' => $this->getResultUrl(),
            'bearerToken' => $this->getBearerToken()
        ];

        $missing = array_filter($required, fn($value) => $value === null);

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required fields for account balance: ' . implode(', ', array_keys($missing))
            );
        }
    }

    public function checkAccountBalance(): array
    {
        $this->validateAccountBalanceFields();

        $payload = [
            'OriginatorConversationID' => 'Partner-' . uniqid(),
            'Initiator' => $this->accountBalanceInitiator,
            'SecurityCredential' => $this->getSecurityCredential(),
            'CommandID' => 'AccountBalance',
            'PartyA' => $this->accountBalancePartyA,
            'IdentifierType' => $this->accountBalanceIdentifierType,
            'Remarks' => $this->accountBalanceRemarks,
            'QueueTimeOutURL' => $this->getQueueTimeoutUrl(),
            'ResultURL' => $this->getResultUrl()
        ];


        $response = $this->makeRequest('/mpesa/accountbalance/v2/query', $payload);

        // Format response to match expected structure
        return [
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success',
            'Result' => $response['Result'] ?? [],
            'ConversationID' => $response['ConversationID'] ?? null,
            'OriginatorConversationID' => $response['OriginatorConversationID'] ?? null
        ];
    }

    public function parseBalanceResult(array $result): array
    {
        if (!isset($result['Result']['ResultParameters']['ResultParameter'])) {
            throw new \RuntimeException('Invalid balance result format');
        }

        $balanceInfo = [];
        foreach ($result['Result']['ResultParameters']['ResultParameter'] as $param) {
            if ($param['Key'] === 'AccountBalance') {
                $accounts = explode('&', $param['Value']);
                foreach ($accounts as $account) {
                    list($name, $currency, $amount) = explode('|', $account);
                    $balanceInfo[] = [
                        'account' => $name,
                        'currency' => $currency,
                        'amount' => $amount
                    ];
                }
                break;
            }
        }

        return $balanceInfo;
    }

    // These methods should be implemented in the main Mpesa class
    abstract protected function getSecurityCredential(): string;
    abstract protected function getQueueTimeoutUrl(): string;
    abstract protected function getResultUrl(): string;
    abstract protected function getBearerToken(): string;
    abstract protected function makeRequest(string $endpoint, array $payload): array;
}