import {useState, useEffect} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, initStripe} from "../util";
import {LocalPaymentIntentContent} from './local-payment-method';
import {PaymentMethod, OffsiteNotice} from "../../components/checkout";
import {AffirmMessageElement, Elements} from "@stripe/react-stripe-js";

const getData = getSettings('stripe_affirm_data');

let msgOptions = {
    amount: getData('cartTotals')?.value,
    currency: getData('currency')
}

const dispatchAffirmChange = (options) => {
    document.dispatchEvent(new CustomEvent('stripeAffirmChange', {
        detail: {options}
    }));
}

const AffirmPaymentMethod = (props) => {
    return (
        <>
            <LocalPaymentIntentContent {...props}/>
            <OffsiteNotice paymentText={getData('title')} buttonText={getData('placeOrderButtonLabel')}/>
        </>
    )
}

const PaymentMethodLabel = ({title, components, ...props}) => {
    const {PaymentMethodLabel: Label} = components;
    const [options, setOptions] = useState({
        amount: getData('cartTotals')?.value,
        currency: getData('currency'),
        ...getData('messageOptions')
    });
    useEffect(() => {
        const updateOptions = (e) => {
            setOptions(e.detail.options);
        }
        document.addEventListener('stripeAffirmChange', updateOptions);

        return () => document.removeEventListener('stripeAffirmChange', updateOptions);
    }, []);

    return (
        <div className={'wc-stripe-label-container'}>
            <Label text={title}/>
            <div className={'wc-stripe-affirm-message-container'}>
                <Elements stripe={initStripe} options={getData('elementOptions')}>
                    <AffirmMessageElement options={options}/>
                </Elements>
            </div>
        </div>
    )
}

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Affirm',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: ({cart}) => {
            const {cartTotals} = cart;
            const {currency_code} = cartTotals;
            const amount = parseInt(cartTotals.total_price);
            dispatchAffirmChange({
                amount: amount,
                currency: currency_code
            });
            return currency_code === 'USD' && 5000 <= amount && amount <= 3000000;
        },
        content: <PaymentMethod
            content={AffirmPaymentMethod}
            getData={getData}
            confirmationMethod={'confirmAffirmPayment'}/>,
        edit: <PaymentMethod content={LocalPaymentIntentContent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}