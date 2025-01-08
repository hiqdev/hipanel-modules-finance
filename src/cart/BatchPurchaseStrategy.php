<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\cart;

use hiqdev\hiart\ResponseErrorException;
use hiqdev\yii2\cart\ShoppingCart;
use Yii;
use yii\base\InvalidParamException;
use yii\web\User;

/**
 * Class BatchPurchaseStrategy purchases positions in batch.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class BatchPurchaseStrategy implements PurchaseStrategyInterface
{
    use PurchaseResultTrait;

    /**
     * @var AbstractCartPosition[]
     */
    protected $positions = [];

    /**
     * @var ShoppingCart
     */
    protected $cart;

    /**
     * @var AbstractPurchase[]
     */
    protected $purchases;
    /**
     * @var User
     */
    private $user;

    /**
     * BatchPurchaseStrategy constructor.
     *
     * @param ShoppingCart $cart
     */
    public function __construct(ShoppingCart $cart, User $user)
    {
        $this->cart = $cart;
        $this->user = $user;
    }

    /** {@inheritdoc} */
    public function addPosition(AbstractCartPosition $position)
    {
        $this->positions[$position->getId()] = $position;
        $this->ensureConsistency();
    }

    /** {@inheritdoc} */
    public function run()
    {
        $this->resetPurchaseResults();
        $this->createPurchaseObjects();
        if (empty($this->purchases)) {
            return;
        }

        $samplePurchase = reset($this->purchases);
        $operation = $samplePurchase::operation();

        try {
            $response = $samplePurchase::perform($operation, $this->collectData(), ['batch' => true]);
            $this->analyzeResponse($response);
        } catch (ResponseErrorException $e) {
            $this->extractResultsFromException($e);
        }
    }

    private function createPurchaseObjects()
    {
        foreach ($this->positions as $id => $position) {
            $this->purchases[$id] = $position->getPurchaseModel();
        }
    }

    private function collectData()
    {
        $result = [];
        foreach ($this->purchases as $id => $purchase) {
            if (!$purchase->validate()) {
                Yii::error('Failed to validate purchase: ' . reset($purchase->getFirstErrors()), __METHOD__);
                $this->error[] = new ErrorPurchaseException('Failed to validate purchase. Contact support.', $purchase);
                continue;
            }

            $result[$id] = $purchase->getAttributes();
        }

        return $result;
    }

    private function extractResultsFromException(ResponseErrorException $e)
    {
        $data = $e->getResponse()->getData();

        if (!is_array($data)) {
            Yii::error('Abnormal response during purchase', __METHOD__);
            throw $e;
        }

        $this->analyzeResponse($data);
    }

    protected function analyzeResponse($response)
    {
        if (isset($response['_error']) && $response['_error'] === 'not enough money') {
            foreach ($this->purchases as $key => $purchase) {
                $error = Yii::t('hipanel:finance', 'Insufficient funds on the balance');
                if (!$user->isAccountOwner()) {
                    $error = Yii::t('hipanel:finance', 'Insufficient funds. Maybe, your client does not have enough money on balance?');
                }

                $this->error[] = new ErrorPurchaseException($error, $purchase);
            }
        }

        foreach ($response as $key => $item) {
            $this->analyzeResponseItem($key, $item);
        }
    }

    protected function analyzeResponseItem($key, $data)
    {
        if (!isset($this->purchases[$key])) {
            return;
        }

        $purchase = $this->purchases[$key];
        if ($error = $this->getPurchaseErrorFromResponse($purchase, $data)) {
            $this->error[] = new ErrorPurchaseException($error, $purchase);
        } elseif ($pendingMessage = $this->getPurchasePendingFromResponse($purchase, $data)) {
            $this->pending[] = new PendingPurchaseException($pendingMessage, $purchase);
        } else {
            $this->success[] = $purchase;
        }
    }

    /**
     * @param AbstractPurchase $purchase
     * @param array $data
     * @return string|null Error message or `null` when no errors found
     */
    protected function getPurchaseErrorFromResponse(AbstractPurchase $purchase, $data)
    {
        if (is_array($data) && array_key_exists('_error', $data)) {
            return $data['_error'];
        }

        return null;
    }

    /**
     * Override this method to detect pending purchase result.
     *
     * @param AbstractPurchase $purchase
     * @param array $data
     * @return string|null Pending reason or `null` when no errors found
     */
    protected function getPurchasePendingFromResponse(AbstractPurchase $purchase, $data)
    {
        return null;
    }

    protected function ensureConsistency()
    {
        $class = null;
        foreach ($this->positions as $id => $position) {
            if ($class === null) {
                $class = get_class($position);
            }

            if (!$position instanceof $class) {
                throw new InvalidParamException('Position "' . $id . '" is violates position class consistency policy');
            }
        }
    }
}
