<?php

namespace Bat\Sales\Model;

/**
 * @class BatOrderStatus
 * Bat custom order status
 */
class BatOrderStatus
{
    public const RETURN_IN_PROGRESS_STATUS = "return_in_progress";
    public const RETURN_IN_PROGRESS_STATUS_LABEL = "Return in Progress";

    public const DELIVERY_FAILED_STATUS = "incomplete";
    public const DELIVERY_FAILED_LABEL = "Incomplete";

    public const ZLOB_IN_PROGRESS_STATUS = "cancel_in_progress";
    public const ZLOB_IN_PROGRESS_STATUS_LABEL = "Cancel in progress";

    public const ZLOB_COMPLETE_STATUS = "zlob_complete";
    public const ZLOB_COMPLETE_STATUS_LABEL = "Completed";

    public const SHIPPED_STATUS = "processing";
    public const SHIPPED_LABEL = "Shipped";

    public const COMPLETED_STATUS = "complete";
    public const COMPLETED_LABEL = "Completed";

    public const FAILURE_STATUS = "failure";
    public const FAILURE_LABEL = "Failure";

    public const PREPARING_TO_SHIP_STATUS = "preparing_to_ship";
    public const PREPARING_TO_SHIP_LABEL = "Preparing to Ship";

    public const UNPAID_STATUS = "pending";
    public const UNPAID_LABEL = "Unpaid";

    public const DELIVERY_CANCELLED = "delivery_canceled";
    public const DELIVERY_CANCELLED_LABEL = "Cancel Order";

    public const RETURN_REQUEST_CLOSED = "return_request_closed";
    public const RETURN_REQUEST_CLOSED_LABEL = "Return Request Closed";

    public const PROCESSING_STATE = 'processing';
    public const COMPLETE_STATE = 'complete';
    public const PENDING_STATE = 'new';
    public const CLOSED_STATE = 'closed';
}
