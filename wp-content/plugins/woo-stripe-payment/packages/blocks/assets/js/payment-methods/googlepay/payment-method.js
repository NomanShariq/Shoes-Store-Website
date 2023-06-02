import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, initStripe as loadStripe, isCartPage} from '../util';
import {useErrorMessage} from "./hooks";
import GooglePayButton from './button';
import {BASE_PAYMENT_METHOD, BASE_PAYMENT_REQUEST} from './constants';
import google from '@googlepay';
import {Elements} from "@stripe/react-stripe-js";

const getData = getSettings('stripe_googlepay_data');

const canMakePayment = (() => {
    const paymentsClient = new google.payments.api.PaymentsClient({
        environment: getData('environment'),
        merchantInfo: {
            merchantId: getData('merchantId'),
            merchantName: getData('merchantName')
        }
    });
    const isReadyToPayRequest = {...BASE_PAYMENT_REQUEST, allowedPaymentMethods: [BASE_PAYMENT_METHOD]};
    return paymentsClient.isReadyToPay(isReadyToPayRequest).then(() => {
        return true;
    }).catch(err => {
        console.log(err);
        return false;
    })
})();

const GooglePayContent = ({getData, components, ...props}) => {
    const {ValidationInputError} = components;
    const {errorMessage, setErrorMessage} = useErrorMessage();
    return (
        <div className='wc-stripe-gpay-container'>
            <Elements stripe={loadStripe}>
                <GooglePayButton getData={getData}
                                 canMakePayment={canMakePayment}
                                 setErrorMessage={setErrorMessage}
                                 {...props}/>
                {errorMessage && <ValidationInputError errorMessage={errorMessage}/>}
            </Elements>
        </div>
    )
}

const GooglePayEdit = ({getData, ...props}) => {
    const buttonType = getData('buttonStyle').buttonType;
    const src = getData('editorIcons').long;
    return (
        <div className={`gpay-block-editor ${buttonType}`}>
            <img src={src}/>
        </div>
    )
}

registerExpressPaymentMethod({
    name: getData('name'),
    canMakePayment: () => {
        if (getData('isAdmin')) {
            if (isCartPage()) {
                return getData('cartCheckoutEnabled');
            }
            return true;
        }
        if (isCartPage() && !getData('cartCheckoutEnabled')) {
            return false;
        }
        return loadStripe.then(stripe => {
            if (stripe.error) {
                return stripe;
            }
            return canMakePayment;
        });
    },
    content: <GooglePayContent getData={getData}/>,
    edit: <GooglePayEdit getData={getData}/>,
    supports: {
        showSavedCards: getData('showSavedCards'),
        showSaveOption: getData('showSaveOption'),
        features: getData('features')
    }
})