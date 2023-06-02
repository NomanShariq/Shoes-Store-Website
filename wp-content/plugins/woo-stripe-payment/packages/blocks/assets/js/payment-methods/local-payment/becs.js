import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings} from "../util";
import {LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";
import {AuBankAccountElement} from "@stripe/react-stripe-js";

const getData = getSettings('stripe_becs_data');

const BecsPaymentMethod = (props) => {
    return (
        <>
            <PaymentMethod {...props} />
            <div className={'wc-stripe-blocks-mandate becs-mandate'}
                 dangerouslySetInnerHTML={{__html: props.getData('mandate')}}/>
        </>
    );
}

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'BECS',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <BecsPaymentMethod
            content={LocalPaymentIntentContent}
            getData={getData}
            confirmationMethod={'confirmAuBecsDebitPayment'}
            component={AuBankAccountElement}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}