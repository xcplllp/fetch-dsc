/**
 * DSC Manager API Client
 * Wraps standard FETCH operations to the local PHP backend
 */
const API = {
    /**
     * Get list of all clients and DSC stats
     */
    async getDSCList() {
        try {
            const response = await fetch('api.php?action=list');
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to fetch DSC records');
            }
            return result;
        } catch (error) {
            console.error('API Error (getDSCList):', error);
            throw error;
        }
    },

    /**
     * Update client DSC attributes
     * @param {number} id - User ID to update
     * @param {object} data - Object containing: dsc_holder_name, dsc_expiry_date, dsc_class, dsc_token_serial
     */
    async updateDSC(id, data) {
        try {
            const response = await fetch('api.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    ...data
                })
            });
            
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to update DSC details');
            }
            return result;
        } catch (error) {
            console.error('API Error (updateDSC):', error);
            throw error;
        }
    }
};
