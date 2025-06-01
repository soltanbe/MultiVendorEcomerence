import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Terminal({ auth }) {
    const [command, setCommand] = useState('');
    const [history, setHistory] = useState([]);

    const runCommand = async (e) => {
        e.preventDefault();
        if (!command.trim()) return;

        setHistory(prev => [...prev, `$ ${command}`]);

        try {
            const res = await axios.post(route('admin.terminal.run'), { command });
            setHistory(prev => [...prev, res.data.output]);
        } catch (err) {
            const output = err.response?.data?.output || '❌ Error running command';
            setHistory(prev => [...prev, output]);
        }

        setCommand('');
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <div className="p-4">
                <h2 className="text-xl font-bold mb-4">Laravel Terminal</h2>
                <div className="bg-black text-green-500 p-4 rounded h-96 overflow-y-auto font-mono text-sm">
                    {history.map((line, idx) => (
                        <div key={idx}>{line}</div>
                    ))}
                </div>
                <form onSubmit={runCommand} className="mt-2 flex items-center space-x-2">
                    <span>$</span>
                    <input
                        type="text"
                        value={command}
                        onChange={(e) => setCommand(e.target.value)}
                        className="bg-black text-green-500 border-none focus:outline-none w-full"
                        autoFocus
                    />
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
