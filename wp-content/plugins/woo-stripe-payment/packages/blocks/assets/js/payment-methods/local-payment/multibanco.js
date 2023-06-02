import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {LocalPaymentSourceContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";

const getData = getSettings('stripe_multibanco_data');

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'MultiBanco',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod content={LocalPaymentSourceContent} getData={getData}/>,
        edit: <PaymentMethod content={LocalPaymentSourceContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}
