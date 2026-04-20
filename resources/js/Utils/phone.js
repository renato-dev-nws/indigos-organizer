const onlyDigits = (value) => String(value || '').replace(/\D/g, '');

export const normalizeBrazilPhoneDigits = (value) => {
    const digits = onlyDigits(value);

    if (!digits) {
        return '';
    }

    if (digits.startsWith('55')) {
        return digits;
    }

    if (digits.length === 10 || digits.length === 11) {
        return `55${digits}`;
    }

    return digits;
};

export const formatBrazilPhone = (value) => {
    const digits = normalizeBrazilPhoneDigits(value);
    if (!digits) {
        return '';
    }

    const local = digits.startsWith('55') ? digits.slice(2) : digits;

    if (local.length === 11) {
        return `+55 (${local.slice(0, 2)}) ${local.slice(2, 7)}-${local.slice(7)}`;
    }

    if (local.length === 10) {
        return `+55 (${local.slice(0, 2)}) ${local.slice(2, 6)}-${local.slice(6)}`;
    }

    return `+${digits}`;
};

export const buildWhatsAppUrl = (value) => {
    const digits = normalizeBrazilPhoneDigits(value);
    if (!digits) {
        return '';
    }

    return `https://wa.me/${digits}`;
};
