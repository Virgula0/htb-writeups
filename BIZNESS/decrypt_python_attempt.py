import hashlib
import base64
import os

# THIS DESCRYPTION SCRIPT DOES NOT WORK SWITCHING TO JAVA TO BE SURE TO OBTAIN THE SAME RESULTS

def get_crypted_bytes(hash_type, salt, bytes_hash):
    try:
        message_digest = hashlib.new(hash_type)
        message_digest.update(salt.encode('utf-8'))
        message_digest.update(bytes_hash)
        return base64.urlsafe_b64encode(message_digest.digest()).decode().replace('+', '.')
    except Exception as e:
        raise Exception("Error while comparing password", e)

# used for plaintext password


def crypt_bytes(hash_type, salt, bytes_hash):
    result = f"${hash_type}${salt}$" + \
        get_crypted_bytes(hash_type, salt, bytes_hash).rstrip("=")
    return result


# Example usage
original_hash = "$SHA1$d$uP0_QaVBpDWFeo8-dRzDqRwXQ2I"
hash_type = "SHA1"
salt = "d"

wordlist_path = "/home/angelo/SecLists/rockyou.txt"
total_lines = sum(1 for line in open(wordlist_path, "r", errors="ignore"))

print("Starting attack... ")

with open(wordlist_path, "r", errors="ignore") as file:
    for i, psw in enumerate(file, start=1):
        crypted = crypt_bytes(hash_type, salt, bytes(psw.encode()))
        percentage = (i / total_lines) * 100
        print(f"Progress: {percentage:.2f}% complete", end="\r")
        # print(f"Trying {psw} hashed {crypted}", end="\r")
        if crypted == original_hash:
            print(f"CRACKED HASH {original_hash} with password {psw}")
            break
