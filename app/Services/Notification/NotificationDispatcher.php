<?php

namespace App\Services\Notification;

use App\Models\Order;
use App\Models\Quotation;
use App\Models\Request\Request;
use App\Notifications\OrderCompleted;
use App\Notifications\OrderConfirmed;
use App\Notifications\OrderDelivered;
use App\Notifications\OrderOutForDelivery;
use App\Notifications\OrderPreparing;
use App\Notifications\OrderReady;
use App\Notifications\QuotationSent;
use Illuminate\Support\Facades\Notification;

/**
 * Fires customer-facing notifications at the right lifecycle points.
 *
 * Orchestrators call into this service rather than emitting notifications
 * directly, so the notification policy (which channels, which audiences,
 * retry behaviour) lives in one place. Notifications are queued by default
 * via the PmbNotification base class.
 */
class NotificationDispatcher
{
    /**
     * Fire when a quotation is sent to the customer.
     */
    public function quotationSent(Quotation $quotation): void
    {
        $customer = $quotation->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new QuotationSent($quotation));
    }

    /**
     * Fire when an order is confirmed (payment received).
     */
    public function orderConfirmed(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderConfirmed($order));
    }

    /**
     * Fire when an order moves into preparation.
     */
    public function orderPreparing(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderPreparing($order));
    }

    /**
     * Fire when an order is ready for collection / dispatch.
     */
    public function orderReady(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderReady($order));
    }

    /**
     * Fire when an order is dispatched for delivery.
     */
    public function orderOutForDelivery(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderOutForDelivery($order));
    }

    /**
     * Fire when an order is delivered.
     */
    public function orderDelivered(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderDelivered($order));
    }

    /**
     * Fire when an order is completed.
     */
    public function orderCompleted(Order $order): void
    {
        $customer = $order->request->customer;
        if (! $customer || ! $customer->user) {
            return;
        }

        Notification::send($customer->user, new OrderCompleted($order));
    }
}