import {stripe} from '@paymentplugins/wc-stripe';
import AfterpayCategoryMessage from './afterpay-category';

if (typeof wc_stripe_bnpl_shop_params !== 'undefined') {
    new AfterpayCategoryMessage(stripe, wc_stripe_bnpl_shop_params);
}