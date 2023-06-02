import {useState, useCallback, useRef} from '@wordpress/element';
import {__} from '@wordpress/i18n';

export const InputCodes = ({onComplete}) => {
    const keyCode = useRef();
    const refs = useRef([]);
    const setRef = useCallback(idx => node => {
        if (node) {
            if (idx === 0) {
                refs.current = [];
            }
            refs.current = [...refs.current, node];
            if (idx === 0) {
                node.focus();
            }
        }
    }, []);
    const onInput = idx => e => {
        let next = idx;
        if (keyCode.current === 8) {
            if (idx > 0) {
                next = idx - 1;
            }
        } else if (idx < 5) {
            next = idx + 1;
        }
        if (refs.current[next]) {
            refs.current[next].focus();
        }
    }

    const onKeyDown = idx => e => {
        keyCode.current = e.keyCode;
        const value = refs.current[idx].value.length;
        if (e.keyCode === 8) {
            if (!value) {
                onInput(idx)();
            }
        } else if (value) {
            onInput(idx)();
        }
    }

    const onChange = value => {
        let codes = [];
        for (const el of refs.current) {
            if (el.value?.length) {
                codes.push(el.value);
            }
        }
        if (codes.length === 6) {
            onComplete(codes);
        }
    }

    return (
        <div className={'wc-stripe-blik-codes-container'}>
            <p>
                {__('Please enter your 6 digit BLIK code.', 'woo-stripe-payment')}
            </p>
            <div className={'wc-stripe-blik-codes'}>
                {[...Array(6).keys()].map(idx => {
                    return <InputCode
                        setRef={setRef(idx)}
                        key={idx}
                        idx={idx}
                        onInput={onInput(idx)}
                        onKeyDown={onKeyDown(idx)}
                        onChange={onChange}
                    />
                })}
            </div>
        </div>
    )
}

const InputCode = (
    {
        setRef,
        onInput,
        onChange,
        onKeyDown,
    }) => {
    return (
        <span>
            <input
                ref={setRef}
                type={'text'}
                maxLength={1}
                onKeyDown={onKeyDown}
                onInput={onInput}
                onChange={e => onChange(e.currentTarget.value)}/>
        </span>
    )
}

export default InputCodes;