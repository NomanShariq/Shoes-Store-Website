import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, isTestMode} from "../util";
import {LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";
import {__} from "@wordpress/i18n";

const getData = getSettings('stripe_klarna_data');

const KlarnaPaymentMethod = (props) => {
    return (
        <>
            {isTestMode() &&
            <div className="wc-stripe-klarna__testmode">
                <label>{__('Test mode sms', 'woo-stripe-payment')}:</label>&nbsp;<span>123456</span>
            </div>}
            <LocalPaymentIntentContent {...props}/>
        </>
    )
}

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Klarna',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData, ({settings, billingData, cartTotals}) => {
            const {country} = billingData;
            const {currency_code: currency} = cartTotals;
            const requiredParams = settings('requiredParams');
            return [currency] in requiredParams && requiredParams[currency].includes(country);
        }),
        content: <PaymentMethod
            content={KlarnaPaymentMethod}
            getData={getData}
            confirmationMethod={'confirmKlarnaPayment'}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}