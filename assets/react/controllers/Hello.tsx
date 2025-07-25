import React from 'react';

interface HelloProps {
    name?: string;
}

export default function Hello({ name = 'World' }: HelloProps) {
    console.log('Hello world!');
    return (
        <div className="alert alert-success">
            <h2>Hello {name}!</h2>
            <p>This is a React component rendered with Symfony UX React.</p>
        </div>
    );
}
