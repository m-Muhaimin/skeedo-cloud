<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\UpdateAffiliateCommandHandler;
use Affiliate\Domain\ValueObjects\BankRequisites;
use Affiliate\Domain\ValueObjects\PayoutMethod;
use Affiliate\Domain\ValueObjects\PayPalEmail;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;

#[Handler(UpdateAffiliateCommandHandler::class)]
class UpdateAffiliateCommand
{
    public UserEntity $user;
    public ?PayoutMethod $payoutMethod = null;
    public ?PayPalEmail $paypalEmail = null;
    public ?BankRequisites $bankRequisites = null;

    public function __construct(UserEntity $user)
    {
        $this->user = $user;
    }

    public function setPayoutMethod(null|string|PayoutMethod $payoutMethod): self
    {
        $this->payoutMethod = is_string($payoutMethod)
            ? PayoutMethod::from($payoutMethod)
            : $payoutMethod;

        return $this;
    }

    public function setPayPalEmail(null|string|PayPalEmail $paypalEmail): self
    {
        $this->paypalEmail = $paypalEmail instanceof PayPalEmail
            ? $paypalEmail
            : new PayPalEmail($paypalEmail);

        return $this;
    }

    public function setBankRequisites(null|string|BankRequisites $bankRequisites): self
    {
        $this->bankRequisites = $bankRequisites instanceof BankRequisites
            ? $bankRequisites
            : new BankRequisites($bankRequisites);

        return $this;
    }
}
