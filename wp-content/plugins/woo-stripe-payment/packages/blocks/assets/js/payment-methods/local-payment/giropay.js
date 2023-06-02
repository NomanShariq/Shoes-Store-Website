import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";

const getData = getSettings('stripe_giropay_data');

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Giropay',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={LocalPaymentIntentContent}
            confirmationMethod={'confirmGiropayPayment'}
            getData={getData}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}