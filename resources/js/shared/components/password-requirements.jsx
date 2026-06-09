import { Check } from 'lucide-react';

const requirements = [
    {
        key: 'hasMinLength',
        label: 'Mínimo 8 caracteres',
        test: (password) => password.length >= 8,
    },
    {
        key: 'hasUppercase',
        label: 'Una letra mayúscula',
        test: (password) => /[A-Z]/.test(password),
    },
    {
        key: 'hasLowercase',
        label: 'Una letra minúscula',
        test: (password) => /[a-z]/.test(password),
    },
    {
        key: 'hasNumber',
        label: 'Un número',
        test: (password) => /\d/.test(password),
    },
    {
        key: 'hasSymbol',
        label: 'Un símbolo',
        test: (password) => /[^A-Za-z0-9]/.test(password),
    },
];

export function validatePasswordRequirements(password = '') {
    const status = requirements.reduce((current, requirement) => ({
        ...current,
        [requirement.key]: requirement.test(password),
    }), {});

    return {
        ...status,
        isValid: Object.values(status).every(Boolean),
    };
}

export default function PasswordRequirements({ password = '' }) {
    const status = validatePasswordRequirements(password);

    return (
        <div className="space-y-1 rounded-md border bg-muted/30 p-3 text-sm">
            {requirements.map((requirement) => {
                const isMet = status[requirement.key];

                return (
                    <div
                        key={requirement.key}
                        className={`flex items-center gap-2 ${
                            isMet ? 'text-green-700 dark:text-green-400' : 'text-muted-foreground'
                        }`}
                    >
                        <span className="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px]">
                            {isMet ? <Check className="h-3 w-3" /> : null}
                        </span>
                        <span>{requirement.label}</span>
                    </div>
                );
            })}
        </div>
    );
}
