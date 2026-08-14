import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect
                x="5"
                y="7"
                width="30"
                height="28"
                rx="7"
                fill="currentColor"
            />
            <path
                d="M12 5V11M28 5V11M5 15H35"
                stroke="white"
                strokeWidth="2.5"
                strokeLinecap="round"
            />
            <path
                d="M13 25L18 29L28 19"
                stroke="white"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
