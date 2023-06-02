import {useState} from '@wordpress/element';

export const useErrorMessage = () => {
    const [errorMessage, setErrorMessage] = useState(false);
    return {errorMessage, setErrorMessage};
}