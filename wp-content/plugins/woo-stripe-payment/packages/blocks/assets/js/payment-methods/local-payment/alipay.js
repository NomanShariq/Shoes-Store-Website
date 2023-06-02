import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {canMakePayment, LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel} from "../../components/checkout/payment-method-label";
import {PaymentMethod} from "../../components/checkout";

const getData = getSettings('stripe_alipay_data');

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Alipay',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={LocalPaymentIntentContent}
            confirmationMethod={'confirmAlipayPayment'}
            getData={getData}/>,
        edit: <PaymentMethod
            content={LocalPaymentIntentContent}
            getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}
