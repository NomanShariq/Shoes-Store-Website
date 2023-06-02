import {getCreditCardForm} from "../../util";
import {cloneElement, useRef, useCallback, useEffect, useState} from '@wordpress/element';
import {useElements, CardNumberElement, CardExpiryElement, CardCvcElement} from '@stripe/react-stripe-js';
import {sprintf, __} from '@wordpress/i18n';
import {useBreakpointWidth} from "../../hooks";

const classes = {
    focus: 'focused',
    empty: 'empty',
    invalid: 'invalid'
}

const CustomCardForm = (
    {
        getData,
        onChange: eventChange,
        onComplete
    }) => {
    const [cardType, setCardType] = useState('');
    const elementOrder = useRef([]);
    const [container, setContainer] = useState(null);
    const elements = useElements();
    const id = getData('customForm');
    const {component: CardForm = null, breakpoint = 475} = getCreditCardForm(id);
    const postalCodeEnabled = getData('postalCodeEnabled');
    const options = {};
    const elementStatus = useRef({'cardNumber': {}, 'cardExpiry': {}, 'cardCvc': {}});
    ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(type => {
        options[type] = {
            classes,
            ...getData('cardOptions'),
            ...getData('customFieldOptions')[type],
        }
    });
    const onChange = (element) => {
        setElementOrder(element);
        return (event) => {
            eventChange(event);
            elementStatus.current[event.elementType] = event;
            if (event.elementType === 'cardNumber') {
                if (event.brand === 'unknown') {
                    setCardType('');
                } else {
                    setCardType(event.brand);
                }
            }
            if (event.complete) {
                const idx = elementOrder.current.indexOf(element);
                if (elementOrder.current[idx + 1]) {
                    const nextElement = elementOrder.current[idx + 1];
                    elements.getElement(nextElement).focus();
                }
            }
            onComplete(isFormComplete());
        }
    }

    const isFormComplete = () => {
        let status = elementStatus.current;
        return Object.keys(status).filter(key => !!status[key].complete).length === Object.keys(status).length;
    }

    const setElementOrder = useCallback((element) => {
        if (!elementOrder.current.includes(element)) {
            elementOrder.current.push(element);
        }
    }, []);

    useBreakpointWidth({name: 'creditCardForm', width: breakpoint, node: container, className: 'small-form'});

    const getCardIconSrc = useCallback((type) => {
        for (let id of Object.keys(getData('cards'))) {
            if (id === type) {
                return getData('cards')[id];
            }
        }
        return '';
    }, []);

    if (!CardForm) {
        return (
            <div className='wc-stripe-custom-form-error'>
                <p>{sprintf(__('%s is not a valid blocks Stripe custom form. Please choose another custom form option in the Credit Card Settings.', 'woo-stripe-payment'), getData('customFormLabels')[id])}</p>
            </div>
        )
    }
    return (
        <div className={`wc-stripe-custom-form ${id}`} ref={setContainer}>
            {cloneElement(CardForm, {
                postalCodeEnabled,
                options,
                onChange,
                CardIcon: <CardIcon type={cardType} src={getCardIconSrc(cardType)}/>
            })}
        </div>
    )

}

const CardIcon = ({type, src}) => {
    if (type) {
        return <img className={`wc-stripe-card ${type}`} src={src}/>
    }
    return null;
}

export default CustomCardForm;
