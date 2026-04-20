const onlyDigits = (value) => String(value || '').replace(/\D/g, '');

export const normalizeCountryCodeInput = (value, fallback = '55') => {
    const digits = onlyDigits(value).slice(0, 3);
    return digits || fallback;
};

export const splitPhoneByCountryCode = (value, fallbackCountryCode = '55') => {
    const fallback = normalizeCountryCodeInput(fallbackCountryCode, '55');
    const digits = onlyDigits(value);

    if (!digits) {
        return { countryCode: fallback, localDigits: '' };
    }

    if (digits.length > 11) {
        return {
            countryCode: digits.slice(0, digits.length - 11) || fallback,
            localDigits: digits.slice(-11),
        };
    }

    return {
        countryCode: fallback,
        localDigits: digits.slice(0, 11),
    };
};

const toBrazilLocalDigits = (value) => {
    const { localDigits } = splitPhoneByCountryCode(value);
    return localDigits;
};

export const formatBrazilPhoneInput = (value) => {
    const digits = toBrazilLocalDigits(value);
    if (!digits) {
        return '';
    }

    const ddd = digits.slice(0, 2);
    const subscriber = digits.slice(2);
    const isNineDigits = digits.length > 10;
    const splitAt = isNineDigits ? 5 : 4;
    const firstChunk = subscriber.slice(0, splitAt);
    const secondChunk = subscriber.slice(splitAt);

    let formatted = ddd ? `(${ddd}` : '';
    if (ddd.length === 2) {
        formatted += ')';
    }
    if (firstChunk) {
        formatted += ` ${firstChunk}`;
    }
    if (secondChunk) {
        formatted += `-${secondChunk}`;
    }

    return formatted;
};

export const normalizeBrazilPhoneDigits = (value) => {
    const { countryCode, localDigits } = splitPhoneByCountryCode(value);
    if (localDigits.length < 10) {
        return '';
    }

    return `${countryCode}${localDigits.slice(0, 11)}`;
};

export const composePhoneWithCountryCode = (countryCode, localValue, fallbackCountryCode = '55') => {
    const code = normalizeCountryCodeInput(countryCode, fallbackCountryCode);
    const localDigits = toBrazilLocalDigits(localValue);

    if (!localDigits) {
        return '';
    }

    return `${code}${localDigits.slice(0, 11)}`;
};

export const formatBrazilPhone = (value, options = {}) => {
    const { countryCode, localDigits } = splitPhoneByCountryCode(value);
    const local = formatBrazilPhoneInput(localDigits);
    if (!local) {
        return '';
    }

    if (options.includeCountryCode) {
        return `+${countryCode} ${local}`;
    }

    return local;
};

export const buildWhatsAppUrl = (value) => {
    const digits = normalizeBrazilPhoneDigits(value);
    if (!digits) {
        return '';
    }

    return `https://wa.me/${digits}`;
};
