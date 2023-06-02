import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, cartContainsPreOrder, cartContainsSubscription} from "../util";
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment, LocalPaymentIntentContent} from "./local-payment-method";
import {IbanElement} from "@stripe/react-stripe-js";

const getData = getSettings('stripe_sepa_data');

const LocalPaymentMethod = (PaymentMethod) => (props) => {
    return (
        <>
            <PaymentMethod {...props}/>
            <div className={'wc-stripe-blocks-mandate sepa-mandate'}
                 dangerouslySetInnerHTML={{__html: props.getData('mandate')}}/>
        </>
    )
}

const SepaPaymentMethod = LocalPaymentMethod(PaymentMethod);

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'SEPA',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <SepaPaymentMethod
            content={LocalPaymentIntentContent}
            getData={getData}
            confirmationMethod={'confirmSepaDebitPayment'}
            component={IbanElement}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}