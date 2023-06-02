import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {canMakePayment, LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {EpsBankElement} from "@stripe/react-stripe-js";

const getData = getSettings('stripe_eps_data');

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'EPS',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={LocalPaymentIntentContent}
            confirmationMethod={'confirmEpsPayment'}
            component={EpsBankElement}
            getData={getData}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}