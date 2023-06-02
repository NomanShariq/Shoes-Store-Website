import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {canMakePayment, LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";

const getData = getSettings('stripe_sofort_data');

const getConfirmationArgs = (billingData) => {
    return {sofort: {country: billingData.country}};
}

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Sofort',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={LocalPaymentIntentContent}
            confirmationMethod={'confirmSofortPayment'}
            getData={getData}
            callback={getConfirmationArgs}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}
