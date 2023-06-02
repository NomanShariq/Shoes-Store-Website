import {BaseGateway, ProductGateway, stripe} from '@paymentplugins/wc-stripe';
import ProductMessaging from './affirm-product';
import CartMessaging from './affirm-cart';
import CheckoutMessaging from './affirm-checkout';
import CategoryMessaging from './affirm-category';

class AffirmGateway extends BaseGateway {
    constructor(params) {
        super(params);
    }
};

if (typeof wc_stripe_affirm_cart_params !== 'undefined') {
    new CartMessaging(new AffirmGateway(wc_stripe_affirm_cart_params));
}
if (typeof wc_stripe_affirm_product_params !== 'undefined') {
    Object.assign(AffirmGateway.prototype, ProductGateway.prototype);
    new ProductMessaging(new AffirmGateway(wc_stripe_affirm_product_params));
}
if (typeof wc_stripe_local_payment_params !== 'undefined') {
    if (wc_stripe_local_payment_params?.gateways?.stripe_affirm) {
        new CheckoutMessaging(new AffirmGateway(wc_stripe_local_payment_params.gateways.stripe_affirm));
    }
}
if (typeof wc_stripe_bnpl_shop_params !== 'undefined') {
    new CategoryMessaging(stripe, wc_stripe_bnpl_shop_params);
}