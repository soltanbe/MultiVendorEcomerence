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
} from '@mui/material';
import axios from 'axios';

const ArtisanConsole = () => {
    const [commands, setCommands] = useState([]);
    const [selected, setSelected] = useState('');
    const [output, setOutput] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        axios.get('/console/commands').then(res => {
            setCommands(res.data.commands);
        });
    }, []);

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
        <Box>
            <Typography variant="h5" gutterBottom>Laravel Artisan Console</Typography>

            <FormControl fullWidth sx={{ mb: 2 }}>
                <InputLabel>Select a Command</InputLabel>
                <Select value={selected} onChange={(e) => setSelected(e.target.value)}>
                    {commands.map((cmd) => (
                        <MenuItem key={cmd} value={cmd}>{cmd}</MenuItem>
                    ))}
                </Select>
            </FormControl>

            <Button variant="contained" onClick={runCommand} disabled={loading || !selected}>
                {loading ? 'Running...' : 'Run Command'}
            </Button>

            {output && (
                <Paper elevation={3} sx={{ mt: 3, p: 2, whiteSpace: 'pre-wrap', background: '#111', color: '#0f0' }}>
                    {output}
                </Paper>
            )}
        </Box>
    );
};

ArtisanConsole.layout = (page) => <MainLayout>{page}</MainLayout>;

export default ArtisanConsole;
