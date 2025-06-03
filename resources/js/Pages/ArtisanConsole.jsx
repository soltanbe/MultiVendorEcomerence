import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import {
    Box,
    Button,
    Typography,
    Paper,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    Alert,
    Container
} from '@mui/material';
import axios from 'axios';

const ArtisanConsole = () => {
    const [commands, setCommands] = useState([]);
    const [selected, setSelected] = useState('');
    const [description, setDescription] = useState('');
    const [output, setOutput] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        axios.get('/console/commands').then(res => {
            setCommands(res.data.commands); // [{name, description}]
        });
    }, []);

    const handleSelect = (value) => {
        setSelected(value);
        const cmd = commands.find(c => c.name === value);
        setDescription(cmd?.description || '');
    };

    const runCommand = async () => {
        if (!selected) return;
        setLoading(true);
        setOutput('');
        try {
            const res = await axios.post('/console/run', { command: selected });
            setOutput(res.data.output || res.data.error);
        } catch (err) {
            setOutput('Failed to run command');
        }
        setLoading(false);
    };

    return (
        <Container maxWidth="md" sx={{ mt: 6 }}>
            <Paper elevation={4} sx={{ p: 4, borderRadius: 3 }}>
                <Typography variant="h5" gutterBottom>
                    🛠 Laravel Artisan Console
                </Typography>

                <FormControl fullWidth sx={{ mb: 2 }}>
                    <InputLabel>Select a Command</InputLabel>
                    <Select
                        value={selected}
                        onChange={(e) => handleSelect(e.target.value)}
                        label="Select a Command"
                    >
                        {commands.map((cmd) => (
                            <MenuItem
                                key={cmd.name}
                                value={cmd.name}
                                title={cmd.description} // This is the replacement for Tooltip
                            >
                                {cmd.name}
                            </MenuItem>
                        ))}
                    </Select>
                </FormControl>

                {description && (
                    <Alert severity="info" sx={{ mb: 2 }}>
                        {description}
                    </Alert>
                )}

                <Button
                    variant="contained"
                    onClick={runCommand}
                    disabled={loading || !selected}
                    sx={{ mb: 3 }}
                >
                    {loading ? 'Running...' : 'Run Command'}
                </Button>

                {output && (
                    <Paper
                        elevation={3}
                        sx={{
                            p: 2,
                            whiteSpace: 'pre-wrap',
                            background: '#111',
                            color: '#0f0',
                            fontFamily: 'monospace',
                        }}
                    >
                        {output}
                    </Paper>
                )}
            </Paper>
        </Container>
    );
};

ArtisanConsole.layout = (page) => <MainLayout>{page}</MainLayout>;

export default ArtisanConsole;
