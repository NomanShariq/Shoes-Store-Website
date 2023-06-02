import {useRef} from '@wordpress/element';

export const useExportedValues = () => {
    const exportedValues = useRef({});
    return exportedValues.current;
}