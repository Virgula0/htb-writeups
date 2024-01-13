#include <stdio.h>

void ReverseXOR(unsigned char* param_1, unsigned long param_2, unsigned char* param_3, long param_4) {
    int local_c;

    local_c = 0;
    for (unsigned long local_10 = 0; local_10 < param_2; local_10 = local_10 + 1) {
        if ((long)local_c == param_4 + -1) {
            local_c = 0;
        }
        param_1[local_10] = param_1[local_10] ^ param_3[local_c];
        local_c = local_c + 1;
    }
    return;
}

// do not worksz
int main() {
    unsigned char local_e8[0x22] = {0x2d, 0x17, 0x55, 0x0c, 0x0c, 0x04, 0x09, 0x67}; // start address
    unsigned char local_f0[8] = {0x65, 0x7a, 0x69, 0x61, 0x6b, 0x61, 0x48};   // end address

    ReverseXOR(local_e8, 0x22, local_f0, 8);

    // Display the modified data
    for (int i = 0; i < 8; ++i) {
        printf("%02X ", local_e8[i]);
    }
    
    // returned address

    //0x486D3C6D67654102

    return 0;
}