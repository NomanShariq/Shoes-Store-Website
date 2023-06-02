import {useState} from '@wordpress/element'

export const useStripeError = () => {
    const [error, setError] = useState(false);
    return [error, setError];
}