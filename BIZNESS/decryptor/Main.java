import org.apache.commons.codec.binary.Base64;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class Main{

    public static void main(String[] args){
        String filepath = "/home/angelo/SecLists/rockyou.txt";
        String original_hash = "$SHA$d$uP0_QaVBpDWFeo8-dRzDqRwXQ2I";
        String algo = "SHA";
        String salt = "d";
        
        try (BufferedReader reader = new BufferedReader(new FileReader(filepath))) {
            String line;
            while ((line = reader.readLine()) != null) {
                try{
                    String result = cryptBytes(algo, salt, line.getBytes());
                    if (original_hash.equals(result)){
                        System.out.println("FONUD "+ line +" valid for the hash " + result);

                        System.exit(0);
                    }
                } catch (Exception e) {
                    System.out.println(e);
                    System.exit(-1);
                }
            }
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    public static String cryptBytes(String hashType, String salt, byte[] bytes) throws Exception{
        StringBuilder sb = new StringBuilder();
        sb.append("$").append(hashType).append("$").append(salt).append("$");
        sb.append(getCryptedBytes(hashType, salt, bytes));
        return sb.toString();
    }


    private static String getCryptedBytes(String hashType, String salt, byte[] bytes) throws Exception {
        try {
            MessageDigest messagedigest = MessageDigest.getInstance(hashType);
            messagedigest.update(salt.getBytes());
            messagedigest.update(bytes);
            return Base64.encodeBase64URLSafeString(messagedigest.digest()).replace('+', '.');
        } catch (Exception e) {
            throw new Exception("Error while comparing password", e);
        }
    }

}