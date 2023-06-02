import {CardElement} from "@stripe/react-stripe-js";
import {isFieldRequired} from "../../util";
import {useMemo} from '@wordpress/element';

const StripeCardForm = ({getData, billing, onChange: eventChange, onComplete}) => {
    const elementStatus = {card: {}};
    const onChange = (event) => {
        eventChange(event);
        elementStatus[event.elementType] = event;
        onComplete(isFormComplete());
    }
    const isFormComplete = () => {
        return Object.keys(elementStatus).filter(type => !!elementStatus[type].complete).length === Object.keys(elementStatus).length;
    }
    const cardOptions = useMemo(() => {
        return {
            ...{
                value: {
                    postalCode: billing?.billingData?.postcode
                },
                hidePostalCode: isFieldRequired('postcode'),
                iconStyle: 'default'
            }, ...getData('cardOptions')
        };
    }, [billing.billingData]);
    return (
        <div className='wc-stripe-inline-form'>
            <CardElement options={cardOptions} onChange={onChange}/>
        </div>
    )
}

export default StripeCardForm;