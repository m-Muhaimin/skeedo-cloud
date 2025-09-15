<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Billing\Application\Commands\ReadPlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Title;
use Billing\Infrastructure\Payments\CryptoPaymentGatewayInterface;
use Billing\Infrastructure\Payments\OfflinePaymentGatewayInterface;
use Billing\Infrastructure\Payments\OffsitePaymentGatewayInterface;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Infrastructure\Payments\PlanAwarePaymentGatewayInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use Presentation\Resources\Api\PlanResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/checkout/[uuid:id]', method: RequestMethod::GET)]
#[Route(path: '/checkout/[custom:id]', method: RequestMethod::GET)]
class ViewCheckoutRequestHandler extends BillingView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private PaymentGatewayFactoryInterface $factory,

        #[Inject('option.billing.custom_credits.enabled')]
        private bool $customCreditsEnabled = false,

        #[Inject('option.billing.custom_credits.rate')]
        private int $customCreditsRate = 0,

        #[Inject('option.billing.currency')]
        private ?string $currency = "USD",
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $amount = (int) ($request->getQueryParams()['amount'] ?? null);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        $ws = $user->getCurrentWorkspace();

        try {
            $plan = $this->getPlan($id, $ws, $amount);
        } catch (Exception $e) {
            return new RedirectResponse('/app/billing/plans');
        }

        $data = [
            'plan' => new PlanResource($plan),
            'is_custom' => $id === 'custom',
            'countries' => Countries::getNames(),
            'voice_count' => $ws->getVoiceCount(),
            'states' => [
                'AL' => 'Alabama',
                'AK' => 'Alaska',
                'AZ' => 'Arizona',
                'AR' => 'Arkansas',
                'CA' => 'California',
                'CO' => 'Colorado',
                'CT' => 'Connecticut',
                'DE' => 'Delaware',
                'FL' => 'Florida',
                'GA' => 'Georgia',
                'HI' => 'Hawaii',
                'ID' => 'Idaho',
                'IL' => 'Illinois',
                'IN' => 'Indiana',
                'IA' => 'Iowa',
                'KS' => 'Kansas',
                'KY' => 'Kentucky',
                'LA' => 'Louisiana',
                'ME' => 'Maine',
                'MD' => 'Maryland',
                'MA' => 'Massachusetts',
                'MI' => 'Michigan',
                'MN' => 'Minnesota',
                'MS' => 'Mississippi',
                'MO' => 'Missouri',
                'MT' => 'Montana',
                'NE' => 'Nebraska',
                'NV' => 'Nevada',
                'NH' => 'New Hampshire',
                'NJ' => 'New Jersey',
                'NM' => 'New Mexico',
                'NY' => 'New York',
                'NC' => 'North Carolina',
                'ND' => 'North Dakota',
                'OH' => 'Ohio',
                'OK' => 'Oklahoma',
                'OR' => 'Oregon',
                'PA' => 'Pennsylvania',
                'RI' => 'Rhode Island',
                'SC' => 'South Carolina',
                'SD' => 'South Dakota',
                'TN' => 'Tennessee',
                'TX' => 'Texas',
                'UT' => 'Utah',
                'VT' => 'Vermont',
                'VA' => 'Virginia',
                'WA' => 'Washington',
                'WV' => 'West Virginia',
                'WI' => 'Wisconsin',
                'WY' => 'Wyoming',
            ]
        ];

        $data = array_merge($data, $this->getGateways($plan));

        return new ViewResponse(
            '/templates/app/billing/checkout.twig',
            $data
        );
    }

    private function getPlan(
        string $id,
        WorkspaceEntity $ws,
        ?int $amount = null
    ): PlanEntity {
        if ($id === 'custom') {
            if (!$this->customCreditsEnabled) {
                throw new Exception('Custom credits are not enabled');
            }

            if (!$ws->getSubscription()) {
                throw new Exception('Workspace does not have a subscription');
            }

            if (!$amount || $amount <= 0) {
                throw new Exception('Invalid amount');
            }

            $fraction = Currencies::getFractionDigits($this->currency);

            // This is temporary plan, it won't be saved to the database
            $plan = new PlanEntity(
                new Title('Credit purchase'),
                new Price($amount),
                BillingCycle::ONE_TIME
            );

            $credits = ($amount / 10 ** $fraction) * $this->customCreditsRate;
            $plan->setCreditCount(new CreditCount($credits));

            return $plan;
        }

        $command = new ReadPlanCommand($id);
        /** @var PlanEntity */
        $plan = $this->dispatcher->dispatch($command);

        if (!$plan->isActive()) {
            throw new Exception('Plan is not active');
        }

        if ($plan->getPrice()->value <= 0) {
            if (!in_array(
                $plan->getBillingCycle()->value,
                ['monthly', 'yearly']
            )) {
                // Only recurring plans can be free
                throw new Exception('Plan is not free');
            }

            if (
                $ws->getSubscription()
                && $ws->getSubscription()->getPlan()->getPrice()->value <= 0
            ) {
                throw new Exception('Workspace has a free plan');
            }
        }

        return $plan;
    }

    private function getGateways(PlanEntity $plan): array
    {
        $gateways = [];
        $cryptoGateways = [];
        $offlineGateways = [];

        try {
            $cardGateway = $this->factory->create(
                PaymentGatewayFactoryInterface::CARD_PAYMENT_GATEWAY_KEY
            );

            if (!$cardGateway->isEnabled()) {
                $cardGateway = null;
            }
        } catch (\Throwable $th) {
            $cardGateway = null;
        }

        foreach ($this->factory as $key => $gateway) {
            if (!$gateway->isEnabled()) {
                continue;
            }

            if (
                $gateway instanceof PlanAwarePaymentGatewayInterface
                && !$gateway->supportsPlan($plan)
            ) {
                continue;
            }

            if ($cardGateway && $cardGateway === $gateway) {
                continue;
            }

            if ($gateway instanceof CryptoPaymentGatewayInterface) {
                $cryptoGateways[$key] = $gateway;
                continue;
            }

            if ($gateway instanceof OffsitePaymentGatewayInterface) {
                $gateways[$key] = $gateway;
                continue;
            }

            if ($gateway instanceof OfflinePaymentGatewayInterface) {
                $offlineGateways[$key] = $gateway;
                continue;
            }
        }

        return [
            'gateways' => $gateways,
            'crypto_gateways' => $cryptoGateways,
            'offline_gateways' => $offlineGateways,
            'card_gateway' => $cardGateway,
        ];
    }
}
